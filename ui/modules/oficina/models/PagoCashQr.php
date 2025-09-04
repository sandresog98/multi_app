<?php
require_once __DIR__ . '/../../../config/database.php';

class PagoCashQr {
    private $conn;
    public function __construct() { $this->conn = getConnection(); }

    public function listConfiar($page = 1, $limit = 20, $search = '', $tipo = 'all', $asignacion = '') {
        $offset = ($page - 1) * $limit;
        $where = [];
        $params = [];

        $tipoFilter = "((c.tipo_transaccion IN ('Pago Efectivo','Pago QR')) OR (c.tipo_transaccion IS NULL AND ((c.descripcion LIKE '%Consignacion Efectivo%' AND c.valor_consignacion > 0) OR (c.descripcion LIKE '%Pago QR%' AND c.valor_consignacion > 0))))";
        if ($tipo === 'efectivo') {
            $tipoFilter = "((c.tipo_transaccion = 'Pago Efectivo') OR (c.tipo_transaccion IS NULL AND c.descripcion LIKE '%Consignacion Efectivo%' AND c.valor_consignacion > 0))";
        } elseif ($tipo === 'qr') {
            $tipoFilter = "((c.tipo_transaccion = 'Pago QR') OR (c.tipo_transaccion IS NULL AND c.descripcion LIKE '%Pago QR%' AND c.valor_consignacion > 0))";
        }
        $where[] = $tipoFilter;

        if (!empty($search)) {
            $where[] = "(c.confiar_id LIKE ? OR c.descripcion LIKE ? OR c.documento LIKE ?)";
            $like = "%$search%";
            $params[] = $like; $params[] = $like; $params[] = $like;
        }
        if ($asignacion === 'asignados') {
            $where[] = "a.cedula IS NOT NULL";
        } elseif ($asignacion === 'no_asignados') {
            $where[] = "a.cedula IS NULL";
        }
        $whereClause = 'WHERE ' . implode(' AND ', $where);

        $sql = "SELECT 
                    c.confiar_id,
                    c.fecha,
                    c.descripcion,
                    c.documento,
                    c.oficina,
                    c.valor_consignacion,
                    c.saldo,
                    CASE 
                      WHEN (c.tipo_transaccion IS NOT NULL) THEN c.tipo_transaccion
                      WHEN (c.descripcion LIKE '%Pago QR%' AND c.valor_consignacion > 0) THEN 'Pago QR'
                      WHEN (c.descripcion LIKE '%Consignacion Efectivo%' AND c.valor_consignacion > 0) THEN 'Pago Efectivo'
                      ELSE NULL
                    END AS tipo_transaccion,
                    a.cedula AS asignado_cedula,
                    sa.nombre AS asignado_nombre,
                    a.link_validacion AS asignado_link,
                    a.comentario AS asignado_comentario,
                    a.fecha_validacion AS asignado_fecha,
                    a.estado AS asignado_estado,
                    a.motivo_no_valido,
                    a.no_valido_por,
                    a.no_valido_fecha
                FROM banco_confiar c
                LEFT JOIN banco_confirmacion_confiar a ON a.confiar_id = c.confiar_id
                LEFT JOIN sifone_asociados sa ON sa.cedula = a.cedula
                $whereClause
                ORDER BY c.fecha DESC, c.valor_consignacion DESC
                LIMIT ? OFFSET ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(array_merge($params, [$limit, $offset]));
        $rows = $stmt->fetchAll();

        $countSql = "SELECT COUNT(*) total FROM banco_confiar c LEFT JOIN banco_confirmacion_confiar a ON a.confiar_id = c.confiar_id $whereClause";
        $stmt2 = $this->conn->prepare($countSql);
        $stmt2->execute($params);
        $total = (int)($stmt2->fetch()['total'] ?? 0);

        return [
            'items' => $rows,
            'total' => $total,
            'pages' => $limit > 0 ? (int)ceil($total / $limit) : 1,
            'current_page' => $page,
        ];
    }

    public function assign($confiarId, $cedula, $link, $comentario = null) {
        if (empty($confiarId) || empty($cedula) || empty($link)) {
            return ['success' => false, 'message' => 'Datos incompletos'];
        }
        try {
            $checkStmt = $this->conn->prepare("SELECT 1 FROM control_asociados WHERE cedula = ? LIMIT 1");
            $checkStmt->execute([$cedula]);
            if (!$checkStmt->fetch()) {
                $ins = $this->conn->prepare("INSERT INTO control_asociados (cedula, estado_activo) VALUES (?, 1) ON DUPLICATE KEY UPDATE cedula = VALUES(cedula)");
                $ins->execute([$cedula]);
            }
            // Upsert en confirmación, dejando estado en 'asignado'
            $del = $this->conn->prepare("DELETE FROM banco_confirmacion_confiar WHERE confiar_id = ?");
            $del->execute([$confiarId]);
            $ins = $this->conn->prepare("INSERT INTO banco_confirmacion_confiar (confiar_id, cedula, link_validacion, comentario, estado) VALUES (?, ?, ?, ?, 'asignado')");
            $ok = $ins->execute([$confiarId, $cedula, $link, $comentario]);
            return ['success' => (bool)$ok];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function removeAssignment($confiarId) {
        try {
            $stmt = $this->conn->prepare("UPDATE banco_confirmacion_confiar SET cedula=NULL, link_validacion=NULL, comentario=NULL, estado='pendiente' WHERE confiar_id = ?");
            $ok = $stmt->execute([$confiarId]);
            return ['success' => (bool)$ok];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function markInvalid($confiarId, $motivo, $userId){
        if (empty($confiarId) || trim($motivo)==='') return ['success'=>false,'message'=>'Motivo requerido'];
        try{
            // No permitir si ya conciliado
            $st = $this->conn->prepare("SELECT estado FROM banco_confirmacion_confiar WHERE confiar_id=? LIMIT 1");
            $st->execute([$confiarId]);
            $row = $st->fetch();
            if ($row && $row['estado']==='conciliado') return ['success'=>false,'message'=>'Ya conciliado'];
            $up = $this->conn->prepare("INSERT INTO banco_confirmacion_confiar (confiar_id, estado, motivo_no_valido, no_valido_por, no_valido_fecha) VALUES (?, 'no_valido', ?, ?, NOW()) ON DUPLICATE KEY UPDATE estado='no_valido', motivo_no_valido=VALUES(motivo_no_valido), no_valido_por=VALUES(no_valido_por), no_valido_fecha=VALUES(no_valido_fecha)");
            $ok = $up->execute([$confiarId, $motivo, (int)$userId]);
            return ['success'=>(bool)$ok];
        }catch(Exception $e){
            return ['success'=>false,'message'=>$e->getMessage()];
        }
    }
}
