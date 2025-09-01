<?php
require_once __DIR__ . '/../../../config/database.php';

class Ticketera {
    private $conn;

    public function __construct(){ $this->conn = getConnection(); }

    public function listarTickets($page,$limit,$filters,$sortBy,$sortDir){
        $offset = ($page-1)*$limit;
        $where=[];$params=[];
        if (!empty($filters['q'])) { $where[] = '(t.resumen LIKE ? OR t.descripcion LIKE ?)'; $params[] = '%'.$filters['q'].'%'; $params[] = '%'.$filters['q'].'%'; }
        if (!empty($filters['estado'])) { $where[] = 't.estado = ?'; $params[] = $filters['estado']; }
        if (!empty($filters['responsable'])) { $where[] = 't.responsable_id = ?'; $params[] = (int)$filters['responsable']; }
        $whereClause = $where? ('WHERE '.implode(' AND ',$where)) : '';
        $allowedSort = [ 'fecha_creacion'=>'t.fecha_creacion', 'id'=>'t.id' ];
        $col = $allowedSort[$sortBy] ?? 't.fecha_creacion';
        $dir = strtoupper($sortDir)==='ASC'?'ASC':'DESC';
        $sql = "SELECT t.id, t.resumen, t.estado, t.fecha_creacion,
                       su.nombre_completo AS solicitante_nombre,
                       ru.nombre_completo AS responsable_nombre
                FROM ticketera_tickets t
                LEFT JOIN control_usuarios su ON su.id = t.solicitante_id
                LEFT JOIN control_usuarios ru ON ru.id = t.responsable_id
                $whereClause
                ORDER BY $col $dir
                LIMIT ? OFFSET ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(array_merge($params, [$limit,$offset]));
        $rows = $stmt->fetchAll();
        $count = $this->conn->prepare("SELECT COUNT(*) AS total FROM ticketera_tickets t $whereClause");
        $count->execute($params);
        $total = (int)($count->fetch()['total'] ?? 0);
        return [ 'items'=>$rows, 'total'=>$total, 'pages'=>($limit>0?(int)ceil($total/$limit):1), 'current_page'=>$page ];
    }

    public function crearTicket($creadorId,$solicitanteId,$responsableId,$categoriaId,$resumen,$descripcion){
        if ($responsableId<=0) return ['success'=>false,'message'=>'Responsable requerido'];
        if ($resumen==='') return ['success'=>false,'message'=>'Resumen requerido'];
        $stmt = $this->conn->prepare("INSERT INTO ticketera_tickets (creador_id, solicitante_id, responsable_id, categoria_id, resumen, descripcion, estado)
                                      VALUES (?, ?, ?, ?, ?, ?, 'Backlog')");
        $ok = $stmt->execute([$creadorId,$solicitanteId,$responsableId,($categoriaId?:null),$resumen,$descripcion]);
        if (!$ok) return ['success'=>false,'message'=>'No se pudo crear el ticket'];
        $ticketId = (int)$this->conn->lastInsertId();
        $ev = $this->conn->prepare("INSERT INTO ticketera_eventos (ticket_id, usuario_id, tipo, estado_nuevo, comentario) VALUES (?, ?, 'cambio_estado', 'Backlog', 'Ticket creado')");
        $ev->execute([$ticketId, $creadorId]);
        return ['success'=>true,'id'=>$ticketId];
    }

    public function obtenerDetalle($id){
        $stmt = $this->conn->prepare("SELECT t.*, 
                    cu.nombre_completo AS creador_nombre,
                    su.nombre_completo AS solicitante_nombre,
                    ru.nombre_completo AS responsable_nombre,
                    cat.nombre AS categoria_nombre
                FROM ticketera_tickets t
                LEFT JOIN control_usuarios cu ON cu.id = t.creador_id
                LEFT JOIN control_usuarios su ON su.id = t.solicitante_id
                LEFT JOIN control_usuarios ru ON ru.id = t.responsable_id
                LEFT JOIN ticketera_categoria cat ON cat.id = t.categoria_id
                WHERE t.id = ?
                LIMIT 1");
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if (!$item) return null;
        $ev = $this->conn->prepare("SELECT e.*, u.nombre_completo AS usuario_nombre 
                                    FROM ticketera_eventos e 
                                    LEFT JOIN control_usuarios u ON u.id = e.usuario_id 
                                    WHERE e.ticket_id = ? ORDER BY e.fecha ASC");
        $ev->execute([$id]);
        $eventos = $ev->fetchAll();
        return ['item'=>$item,'eventos'=>$eventos];
    }

    public function comentar($ticketId, $usuarioId, $comentario){
        if (trim($comentario)==='') return ['success'=>false,'message'=>'Comentario requerido'];
        $stmt = $this->conn->prepare("INSERT INTO ticketera_eventos (ticket_id, usuario_id, tipo, comentario) VALUES (?, ?, 'comentario', ?)");
        $ok = $stmt->execute([$ticketId, $usuarioId, $comentario]);
        if (!$ok) return ['success'=>false,'message'=>'No se pudo comentar'];
        return ['success'=>true];
    }

    public function cambiarEstado($ticketId, $usuarioId, $nuevoEstado, $comentario){
        $ticket = $this->conn->prepare("SELECT id, estado, responsable_id, solicitante_id FROM ticketera_tickets WHERE id = ?");
        $ticket->execute([$ticketId]);
        $t = $ticket->fetch();
        if (!$t) return ['success'=>false,'message'=>'Ticket no encontrado'];
        $actual = $t['estado'];
        $responsableId = (int)$t['responsable_id'];
        $solicitanteId = (int)$t['solicitante_id'];
        $nuevo = $nuevoEstado;

        // Reglas de transición
        $esResponsable = ((int)$usuarioId === $responsableId);
        $esSolicitante = ((int)$usuarioId === $solicitanteId);
        $permitida = false;
        if ($actual === 'Backlog' && $esResponsable && in_array($nuevo, ['En Curso','Rechazado'])) $permitida = true;
        if ($actual === 'En Curso' && $esResponsable && in_array($nuevo, ['En Espera','Resuelto'])) $permitida = true;
        if ($actual === 'En Espera' && $esResponsable && in_array($nuevo, ['En Curso','Resuelto'])) $permitida = true;
        if ($actual === 'Resuelto' && $esSolicitante && in_array($nuevo, ['Aceptado','Rechazado'])) $permitida = true;
        // Si solicitante rechaza en Resuelto, vuelve a En Curso
        if ($actual === 'Resuelto' && $esSolicitante && $nuevo === 'Rechazado') { $nuevo = 'En Curso'; $permitida = true; }

        if (!$permitida) return ['success'=>false,'message'=>'Transición no permitida'];

        $up = $this->conn->prepare("UPDATE ticketera_tickets SET estado = ?, fecha_actualizacion = CURRENT_TIMESTAMP WHERE id = ?");
        $ok = $up->execute([$nuevo, $ticketId]);
        if (!$ok) return ['success'=>false,'message'=>'No se pudo cambiar estado'];
        $ins = $this->conn->prepare("INSERT INTO ticketera_eventos (ticket_id, usuario_id, tipo, estado_anterior, estado_nuevo, comentario) VALUES (?, ?, 'cambio_estado', ?, ?, ?)");
        $ins->execute([$ticketId, $usuarioId, $actual, $nuevo, $comentario]);
        return ['success'=>true];
    }
}


