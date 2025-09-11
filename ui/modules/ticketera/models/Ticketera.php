<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../models/Logger.php';

class Ticketera {
    private $conn;

    public function __construct(){ $this->conn = getConnection(); }

    public function listarTickets($page,$limit,$filters,$sortBy,$sortDir){
        $offset = ($page-1)*$limit;
        $where=[];$params=[];
        if (!empty($filters['q'])) { $where[] = '(t.resumen LIKE ? OR t.descripcion LIKE ?)'; $params[] = '%'.$filters['q'].'%'; $params[] = '%'.$filters['q'].'%'; }
        if (!empty($filters['estado'])) { $where[] = 't.estado = ?'; $params[] = $filters['estado']; }
        if (!empty($filters['responsable'])) { $where[] = 't.responsable_id = ?'; $params[] = (int)$filters['responsable']; }
        if (!empty($filters['solicitante'])) { $where[] = 't.solicitante_id = ?'; $params[] = (int)$filters['solicitante']; }
        $whereClause = $where? ('WHERE '.implode(' AND ',$where)) : '';
        $allowedSort = [ 'fecha_creacion'=>'t.fecha_creacion', 'id'=>'t.id' ];
        $col = $allowedSort[$sortBy] ?? 't.fecha_creacion';
        $dir = strtoupper($sortDir)==='ASC'?'ASC':'DESC';
        $sql = "SELECT t.id, t.resumen, t.estado, t.fecha_creacion,
                       su.nombre_completo AS solicitante_nombre,
                       ru.nombre_completo AS responsable_nombre,
                       c.nombre AS categoria_nombre
                FROM ticketera_tickets t
                LEFT JOIN control_usuarios su ON su.id = t.solicitante_id
                LEFT JOIN control_usuarios ru ON ru.id = t.responsable_id
                LEFT JOIN ticketera_categoria c ON c.id = t.categoria_id
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
        // Log
        try { (new Logger())->logCrear('ticketera.tickets', "Ticket creado #$ticketId", [
            'ticket_id'=>$ticketId,
            'creador_id'=>$creadorId,
            'solicitante_id'=>$solicitanteId,
            'responsable_id'=>$responsableId,
            'categoria_id'=>$categoriaId,
            'resumen'=>$resumen
        ]); } catch (Throwable $e) { /* ignore */ }
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
        $ev = $this->conn->prepare("SELECT e.*, u.nombre_completo AS usuario_nombre,
                                        ra.nombre_completo AS responsable_anterior_nombre,
                                        rn.nombre_completo AS responsable_nuevo_nombre
                                    FROM ticketera_eventos e 
                                    LEFT JOIN control_usuarios u ON u.id = e.usuario_id 
                                    LEFT JOIN control_usuarios ra ON ra.id = e.responsable_anterior_id
                                    LEFT JOIN control_usuarios rn ON rn.id = e.responsable_nuevo_id
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
        // Log
        try { (new Logger())->logCrear('ticketera.tickets', "Comentario en ticket #$ticketId", [
            'ticket_id'=>$ticketId,
            'usuario_id'=>$usuarioId,
            'comentario'=>$comentario
        ]); } catch (Throwable $e) { /* ignore */ }
        return ['success'=>true];
    }

    public function cambiarEstado($ticketId, $usuarioId, $nuevoEstado, $comentario){
        if (trim($comentario) === '') {
            return ['success'=>false,'message'=>'Comentario requerido'];
        }
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
        // Log
        try { (new Logger())->logEditar('ticketera.tickets', "Cambio de estado ticket #$ticketId: $actual → $nuevo", [ 'estado'=>$actual ], [ 'estado'=>$nuevo, 'comentario'=>$comentario ]); } catch (Throwable $e) { /* ignore */ }
        return ['success'=>true];
    }

    public function reasignar($ticketId, $usuarioId, $nuevoResponsableId, $comentario){
        if (trim($comentario) === '') {
            return ['success'=>false,'message'=>'Comentario requerido'];
        }
        $stmt = $this->conn->prepare("SELECT id, responsable_id, estado FROM ticketera_tickets WHERE id = ?");
        $stmt->execute([$ticketId]);
        $t = $stmt->fetch();
        if(!$t) return ['success'=>false,'message'=>'Ticket no encontrado'];
        $anterior = (int)$t['responsable_id'];
        $estadoActual = (string)$t['estado'];
        if (in_array($estadoActual, ['Aceptado','Rechazado','Resuelto'], true)) {
            return ['success'=>false,'message'=>'No se puede reasignar en este estado'];
        }
        if ($anterior === (int)$nuevoResponsableId) return ['success'=>false,'message'=>'El responsable ya es ese usuario'];
        // Solo el responsable actual puede reasignar
        if ($anterior !== (int)$usuarioId) return ['success'=>false,'message'=>'No autorizado'];
        // Actualizar responsable y enviar a Backlog
        $up = $this->conn->prepare("UPDATE ticketera_tickets SET responsable_id = ?, estado = 'Backlog', fecha_actualizacion = CURRENT_TIMESTAMP WHERE id = ?");
        $ok = $up->execute([(int)$nuevoResponsableId, $ticketId]);
        if(!$ok) return ['success'=>false,'message'=>'No se pudo reasignar'];
        // Registrar evento de reasignación
        $ins = $this->conn->prepare("INSERT INTO ticketera_eventos (ticket_id, usuario_id, tipo, responsable_anterior_id, responsable_nuevo_id, comentario) VALUES (?, ?, 'reasignacion', ?, ?, ?)");
        $ins->execute([$ticketId, $usuarioId, $anterior, (int)$nuevoResponsableId, $comentario]);
        // Registrar evento de cambio de estado a Backlog
        $evEstado = $this->conn->prepare("INSERT INTO ticketera_eventos (ticket_id, usuario_id, tipo, estado_anterior, estado_nuevo, comentario) VALUES (?, ?, 'cambio_estado', ?, 'Backlog', ?)");
        $evEstado->execute([$ticketId, $usuarioId, $estadoActual, 'Reasignación']);
        // Log auditoría
        try { (new Logger())->logEditar('ticketera.tickets', "Reasignación de ticket #$ticketId", [ 'responsable_id'=>$anterior, 'estado'=>$estadoActual ], [ 'responsable_id'=>(int)$nuevoResponsableId, 'estado'=>'Backlog', 'comentario'=>$comentario ]); } catch (Throwable $e) { /* ignore */ }
        return ['success'=>true];
    }

    // Resumen/KPIs
    public function obtenerKpisResumen(){
        $kpis = [
            'tickets_creados' => 0,
            'tickets_abiertos' => 0,
            'promedio_horas_abierto' => 0.0,
            'aceptados_semana' => 0,
        ];
        // Total creados
        $q1 = $this->conn->query("SELECT COUNT(*) AS c FROM ticketera_tickets");
        $kpis['tickets_creados'] = (int)($q1->fetch()['c'] ?? 0);
        // Abiertos (no aceptados)
        $q2 = $this->conn->query("SELECT COUNT(*) AS c FROM ticketera_tickets WHERE estado <> 'Aceptado'");
        $kpis['tickets_abiertos'] = (int)($q2->fetch()['c'] ?? 0);
        // Promedio horas abierto: desde creación hasta Aceptado, sólo tickets aceptados
        $sqlAvg = "SELECT AVG(TIMESTAMPDIFF(HOUR, t.fecha_creacion, e.fecha)) AS avg_hours
                   FROM ticketera_tickets t
                   INNER JOIN (
                       SELECT ticket_id, MIN(fecha) AS fecha
                       FROM ticketera_eventos
                       WHERE tipo='cambio_estado' AND estado_nuevo='Aceptado'
                       GROUP BY ticket_id
                   ) e ON e.ticket_id = t.id";
        $rowAvg = $this->conn->query($sqlAvg)->fetch();
        $kpis['promedio_horas_abierto'] = round((float)($rowAvg['avg_hours'] ?? 0), 1);
        // Aceptados en últimos 7 días
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS c
            FROM ticketera_eventos e
            WHERE e.tipo='cambio_estado' AND e.estado_nuevo='Aceptado' AND e.fecha >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $stmt->execute();
        $kpis['aceptados_semana'] = (int)($stmt->fetch()['c'] ?? 0);
        return $kpis;
    }

    public function distribucionPorEstado(){
        $stmt = $this->conn->query("SELECT estado, COUNT(*) AS cantidad
            FROM ticketera_tickets
            WHERE estado <> 'Aceptado'
            GROUP BY estado
            ORDER BY cantidad DESC");
        return $stmt->fetchAll();
    }

    public function abiertosPorUsuario(){
        $stmt = $this->conn->query("SELECT t.responsable_id AS id, COALESCE(u.nombre_completo,'Sin asignar') AS nombre, COUNT(*) AS cantidad
            FROM ticketera_tickets t
            LEFT JOIN control_usuarios u ON u.id = t.responsable_id
            WHERE t.estado <> 'Aceptado'
            GROUP BY t.responsable_id, u.nombre_completo
            ORDER BY cantidad DESC");
        return $stmt->fetchAll();
    }

    public function cerradosPorUsuario($dias, $limit){
        $dias = max(1, (int)$dias); $limit = max(1, (int)$limit);
        $sql = "SELECT t.responsable_id AS id, COALESCE(u.nombre_completo,'Sin asignar') AS nombre, COUNT(*) AS cantidad
                FROM ticketera_eventos e
                INNER JOIN ticketera_tickets t ON t.id = e.ticket_id
                LEFT JOIN control_usuarios u ON u.id = t.responsable_id
                WHERE e.tipo='cambio_estado' AND e.estado_nuevo='Aceptado' AND e.fecha >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY t.responsable_id, u.nombre_completo
                ORDER BY cantidad DESC
                LIMIT ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$dias, $limit]);
        return $stmt->fetchAll();
    }

    public function creadosPorUsuario($dias, $limit){
        $dias = max(1, (int)$dias); $limit = max(1, (int)$limit);
        $sql = "SELECT t.creador_id AS id, cu.nombre_completo AS creador_nombre, COUNT(*) AS cantidad,
                       MIN(su.nombre_completo) AS solicitante_ejemplo
                FROM ticketera_tickets t
                LEFT JOIN control_usuarios cu ON cu.id = t.creador_id
                LEFT JOIN control_usuarios su ON su.id = t.solicitante_id
                WHERE t.fecha_creacion >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY t.creador_id, cu.nombre_completo
                ORDER BY cantidad DESC
                LIMIT ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$dias, $limit]);
        return $stmt->fetchAll();
    }

    public function solicitadosPorUsuario($dias, $limit){
        $dias = max(1, (int)$dias); $limit = max(1, (int)$limit);
        $sql = "SELECT t.solicitante_id AS id, su.nombre_completo AS solicitante_nombre, COUNT(*) AS cantidad,
                       MIN(cu.nombre_completo) AS creador_ejemplo
                FROM ticketera_tickets t
                LEFT JOIN control_usuarios su ON su.id = t.solicitante_id
                LEFT JOIN control_usuarios cu ON cu.id = t.creador_id
                WHERE t.fecha_creacion >= DATE_SUB(NOW(), INTERVAL ? DAY)
                  AND t.solicitante_id <> t.creador_id
                GROUP BY t.solicitante_id, su.nombre_completo
                ORDER BY cantidad DESC
                LIMIT ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$dias, $limit]);
        return $stmt->fetchAll();
    }
}


