<?php
require_once __DIR__ . '/../../../config/database.php';

class Asociado {
    private $conn;
    public function __construct() { $this->conn = getConnection(); }

    public function getAsociados($page = 1, $limit = 20, $search = '', $estado = '') {
        try {
            $offset = ($page - 1) * $limit;
            $where = [];
            $params = [];
            if (!empty($search)) {
                $where[] = "(a.cedula LIKE ? OR a.nombre LIKE ? OR a.celula LIKE ? OR a.mail LIKE ?)";
                $like = "%$search%"; $params = array_merge($params, [$like,$like,$like,$like]);
            }
            if ($estado !== '') {
                if ($estado === 'activo') $where[] = "ca.estado_activo = TRUE";
                if ($estado === 'inactivo') $where[] = "ca.estado_activo = FALSE";
            }
            $whereClause = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));
            $sql = "SELECT a.cedula, a.nombre, a.mail, a.celula, COALESCE(ca.estado_activo, FALSE) as estado_activo
                    FROM sifone_asociados a
                    LEFT JOIN control_asociados ca ON a.cedula = ca.cedula
                    $whereClause
                    ORDER BY a.nombre
                    LIMIT ? OFFSET ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(array_merge($params, [$limit, $offset]));
            $rows = $stmt->fetchAll();

            $countSql = "SELECT COUNT(*) as total FROM sifone_asociados a LEFT JOIN control_asociados ca ON a.cedula = ca.cedula $whereClause";
            $countStmt = $this->conn->prepare($countSql);
            $countStmt->execute($params);
            $total = (int)($countStmt->fetch()['total'] ?? 0);

            return ['asociados'=>$rows,'total'=>$total,'pages'=>$limit?ceil($total/$limit):1,'current_page'=>$page];
        } catch (Exception $e) { return ['asociados'=>[],'total'=>0,'pages'=>1,'current_page'=>1]; }
    }

    public function updateEstado($cedula, $estado) {
        try {
            $estadoInt = $estado ? 1 : 0;
            $stmt = $this->conn->prepare("INSERT INTO control_asociados (cedula, estado_activo, fecha_actualizacion) VALUES (?, ?, CURRENT_TIMESTAMP)
                                          ON DUPLICATE KEY UPDATE estado_activo = VALUES(estado_activo), fecha_actualizacion = CURRENT_TIMESTAMP");
            return $stmt->execute([$cedula, $estadoInt]);
        } catch (Exception $e) { return false; }
    }
}
