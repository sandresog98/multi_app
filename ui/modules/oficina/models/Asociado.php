<?php
require_once __DIR__ . '/../../../config/database.php';

class Asociado {
    private $conn;
    public function __construct() { $this->conn = getConnection(); }

    public function getAsociados($page = 1, $limit = 20, $search = '', $estado = '', $productos = '') {
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
            // Joins para saber si tiene productos y/o créditos
            $join = " LEFT JOIN (
                           SELECT cedula, COUNT(*) AS cant_prod
                           FROM control_asignacion_asociado_producto ap
                           WHERE ap.estado_activo = TRUE
                           GROUP BY cedula
                       ) ap ON ap.cedula = a.cedula
                       LEFT JOIN (
                           SELECT cedula, COUNT(*) AS cant_cred
                           FROM sifone_cartera_aseguradora
                           GROUP BY cedula
                       ) cr ON cr.cedula = a.cedula";

            // Filtro por combinación de productos/créditos
            if ($productos !== '') {
                if ($productos === 'sin_productos') { $where[] = '(COALESCE(ap.cant_prod,0)=0 AND COALESCE(cr.cant_cred,0)=0)'; }
                elseif ($productos === 'con_productos') { $where[] = '(COALESCE(ap.cant_prod,0)>0 AND COALESCE(cr.cant_cred,0)=0)'; }
                elseif ($productos === 'con_creditos') { $where[] = '(COALESCE(ap.cant_prod,0)=0 AND COALESCE(cr.cant_cred,0)>0)'; }
                elseif ($productos === 'con_ambos') { $where[] = '(COALESCE(ap.cant_prod,0)>0 AND COALESCE(cr.cant_cred,0)>0)'; }
            }

            $whereClause = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));
            $sql = "SELECT a.cedula, a.nombre, a.mail, a.celula,
                           COALESCE(ca.estado_activo, FALSE) as estado_activo,
                           COALESCE(ap.cant_prod,0) AS productos_cant,
                           COALESCE(cr.cant_cred,0) AS creditos_cant
                    FROM sifone_asociados a
                    LEFT JOIN control_asociados ca ON a.cedula = ca.cedula
                    $join
                    $whereClause
                    ORDER BY a.nombre
                    LIMIT ? OFFSET ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(array_merge($params, [$limit, $offset]));
            $rows = $stmt->fetchAll();

            $countSql = "SELECT COUNT(*) as total FROM sifone_asociados a LEFT JOIN control_asociados ca ON a.cedula = ca.cedula $join $whereClause";
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

    public function getKpisProductosEstados(){
        $sql = "SELECT 
                  SUM(CASE WHEN ca.estado_activo = TRUE THEN 1 ELSE 0 END) AS activos,
                  SUM(CASE WHEN ca.estado_activo = FALSE THEN 1 ELSE 0 END) AS inactivos,
                  SUM(CASE WHEN COALESCE(ap.cant_prod,0)=0 AND COALESCE(cr.cant_cred,0)=0 THEN 1 ELSE 0 END) AS sin_productos,
                  SUM(CASE WHEN COALESCE(ap.cant_prod,0)>0 AND COALESCE(cr.cant_cred,0)=0 THEN 1 ELSE 0 END) AS con_productos,
                  SUM(CASE WHEN COALESCE(ap.cant_prod,0)=0 AND COALESCE(cr.cant_cred,0)>0 THEN 1 ELSE 0 END) AS con_creditos,
                  SUM(CASE WHEN COALESCE(ap.cant_prod,0)>0 AND COALESCE(cr.cant_cred,0)>0 THEN 1 ELSE 0 END) AS con_ambos
                FROM sifone_asociados a
                LEFT JOIN control_asociados ca ON ca.cedula = a.cedula
                LEFT JOIN (
                   SELECT cedula, COUNT(*) AS cant_prod
                   FROM control_asignacion_asociado_producto WHERE estado_activo = TRUE GROUP BY cedula
                ) ap ON ap.cedula = a.cedula
                LEFT JOIN (
                   SELECT cedula, COUNT(*) AS cant_cred
                   FROM sifone_cartera_aseguradora GROUP BY cedula
                ) cr ON cr.cedula = a.cedula";
        return $this->conn->query($sql)->fetch() ?: [];
    }
}
