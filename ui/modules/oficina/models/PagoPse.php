<?php
require_once __DIR__ . '/../../../config/database.php';

class PagoPse {
    private $conn;

    public function __construct() {
        $this->conn = getConnection();
    }

    public function getPseList($page = 1, $limit = 20, $search = '', $estado = 'Aprobada', $asignacion = '') {
        $offset = ($page - 1) * $limit;
        $where = [];
        $params = [];

        if (!empty($search)) {
            $where[] = "(p.pse_id LIKE ? OR p.referencia_1 LIKE ?)";
            $like = "%$search%";
            $params[] = $like; $params[] = $like;
        }
        if (!empty($estado)) {
            $where[] = "p.estado = ?";
            $params[] = $estado;
        }
        if ($asignacion === 'asignados') {
            $where[] = "a.confiar_id IS NOT NULL";
        } elseif ($asignacion === 'no_asignados') {
            $where[] = "a.confiar_id IS NULL";
        }

        $whereClause = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));

        $sql = "SELECT 
                       p.pse_id,
                       p.valor,
                       p.estado,
                       p.fecha_hora_resolucion_de_la_transaccion AS fecha,
                       p.referencia_1,
                       p.referencia_2,
                       p.referencia_3,
                       p.servicio_nombre,
                       p.banco_recaudador,
                       p.banco_originador,
                       p.medio_de_pago,
                       p.nombre_funcionalidad,
                       a.confiar_id,
                       a.tipo_asignacion,
                       c.fecha AS asignado_fecha,
                       c.descripcion AS asignado_descripcion,
                       c.documento AS asignado_documento,
                       c.valor_consignacion AS asignado_valor,
                       c.saldo AS asignado_saldo,
                       c.oficina AS asignado_oficina,
                       CASE 
                         WHEN (c.descripcion LIKE '%ACH%' AND c.valor_consignacion > 0) THEN 'Pago ACH'
                         WHEN (c.descripcion LIKE '%Pago QR%' AND c.valor_consignacion > 0) THEN 'Pago QR'
                         WHEN (c.descripcion LIKE '%Consignacion Efectivo%' AND c.valor_consignacion > 0) THEN 'Pago Efectivo'
                         ELSE NULL
                       END AS asignado_tipo
                FROM banco_pse p
                LEFT JOIN banco_asignacion_pse a ON a.pse_id = p.pse_id
                LEFT JOIN banco_confiar c ON c.confiar_id = a.confiar_id
                $whereClause
                ORDER BY p.fecha_hora_resolucion_de_la_transaccion DESC
                LIMIT ? OFFSET ?";
        $paramsExec = array_merge($params, [$limit, $offset]);
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($paramsExec);
        $rows = $stmt->fetchAll();

        $countSql = "SELECT COUNT(*) total FROM banco_pse p LEFT JOIN banco_asignacion_pse a ON a.pse_id = p.pse_id $whereClause";
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

    public function getPseById($pseId) {
        $stmt = $this->conn->prepare("SELECT * FROM banco_pse WHERE pse_id = ?");
        $stmt->execute([$pseId]);
        return $stmt->fetch();
    }

    public function getAssignment($pseId) {
        $stmt = $this->conn->prepare("SELECT * FROM banco_asignacion_pse WHERE pse_id = ? ORDER BY fecha_validacion DESC LIMIT 1");
        $stmt->execute([$pseId]);
        return $stmt->fetch();
    }

    public function getConfiarMatchesForPse($pseId, $q = '', $tolerancia = 0, $fechaFiltro = null) {
        $pse = $this->getPseById($pseId);
        if (!$pse) return [];
        $fecha = $fechaFiltro ? substr($fechaFiltro, 0, 10) : substr($pse['fecha_hora_resolucion_de_la_transaccion'], 0, 10);

        // Filtrar solo por fecha (y por ACH), sin exigir coincidencia exacta de valor
        $where = ["c.fecha = ?"]; 
        $params = [$fecha];
        // Solo pagos ACH (no depender de columna tipo_transaccion)
        $where[] = "(c.descripcion LIKE '%ACH%' AND c.valor_consignacion > 0)";
        if (!empty($q)) {
            $where[] = "(c.confiar_id LIKE ? OR c.descripcion LIKE ? OR c.documento LIKE ?)";
            $like = "%$q%";
            $params[] = $like; $params[] = $like; $params[] = $like;
        }

        $sql = "SELECT 
                    c.confiar_id,
                    c.fecha,
                    c.descripcion,
                    c.documento,
                    c.oficina,
                    /* Derivar tipo si columna no existe o está vacía */
                    CASE 
                      WHEN (c.descripcion LIKE '%ACH%' AND c.valor_consignacion > 0) THEN 'Pago ACH'
                      WHEN (c.descripcion LIKE '%Pago QR%' AND c.valor_consignacion > 0) THEN 'Pago QR'
                      WHEN (c.descripcion LIKE '%Consignacion Efectivo%' AND c.valor_consignacion > 0) THEN 'Pago Efectivo'
                      ELSE NULL
                    END AS tipo_transaccion,
                    c.valor_consignacion,
                    c.saldo,
                    COALESCE(asg.asignado_total, 0) AS asignado_total,
                    (c.valor_consignacion - COALESCE(asg.asignado_total, 0)) AS capacidad_restante
                FROM banco_confiar c
                LEFT JOIN (
                    SELECT a.confiar_id, SUM(p.valor) AS asignado_total
                    FROM banco_asignacion_pse a
                    JOIN banco_pse p ON p.pse_id = a.pse_id
                    GROUP BY a.confiar_id
                ) asg ON asg.confiar_id = c.confiar_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY c.fecha DESC, c.valor_consignacion DESC
                LIMIT 50";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        // Fallback: si no hay cruces por (fecha, valor) y/o búsqueda, traer más recientes (sin limitar a 3)
        if (!$rows || count($rows) === 0) {
            $fallbackSql = "SELECT 
                                c.confiar_id,
                                c.fecha,
                                c.descripcion,
                                c.documento,
                                c.oficina,
                                CASE 
                                  WHEN (c.descripcion LIKE '%ACH%' AND c.valor_consignacion > 0) THEN 'Pago ACH'
                                  WHEN (c.descripcion LIKE '%Pago QR%' AND c.valor_consignacion > 0) THEN 'Pago QR'
                                  WHEN (c.descripcion LIKE '%Consignacion Efectivo%' AND c.valor_consignacion > 0) THEN 'Pago Efectivo'
                                  ELSE NULL
                                END AS tipo_transaccion,
                                c.valor_consignacion,
                                c.saldo,
                                COALESCE(asg.asignado_total, 0) AS asignado_total,
                                (c.valor_consignacion - COALESCE(asg.asignado_total, 0)) AS capacidad_restante
                            FROM banco_confiar c
                            LEFT JOIN (
                                SELECT a.confiar_id, SUM(p.valor) AS asignado_total
                                FROM banco_asignacion_pse a
                                JOIN banco_pse p ON p.pse_id = a.pse_id
                                GROUP BY a.confiar_id
                            ) asg ON asg.confiar_id = c.confiar_id
                            WHERE (c.descripcion LIKE '%ACH%' AND c.valor_consignacion > 0)
                            ORDER BY c.fecha DESC, c.valor_consignacion DESC
                            LIMIT 50";
            $stmt2 = $this->conn->prepare($fallbackSql);
            $stmt2->execute();
            $rows = $stmt2->fetchAll();
        }

        return $rows;
    }

    public function assignConfiar($pseId, $confiarId) {
        // Si ya existe una asignación para este PSE, la reemplazamos
        $this->conn->beginTransaction();
        try {
            // Validar que el confiar es ACH
            $valStmt = $this->conn->prepare("SELECT confiar_id, tipo_transaccion, descripcion, valor_consignacion FROM banco_confiar WHERE confiar_id = ? LIMIT 1");
            $valStmt->execute([$confiarId]);
            $confiar = $valStmt->fetch();
            if (!$confiar) {
                throw new Exception('Registro Confiar no encontrado');
            }
            $tipo = $confiar['tipo_transaccion'] ?? null;
            $esAchDerivado = (stripos($confiar['descripcion'] ?? '', 'ACH') !== false) && ((float)($confiar['valor_consignacion'] ?? 0) > 0);
            if ($tipo !== 'Pago ACH' && !$esAchDerivado) {
                throw new Exception('Solo se permiten asignaciones a registros Confiar con tipo Pago ACH');
            }

            // Bloqueo por capacidad: suma de PSE ya asignados no debe exceder valor_consignacion
            $capStmt = $this->conn->prepare("SELECT COALESCE(SUM(p.valor),0) AS asignado_total FROM banco_asignacion_pse a JOIN banco_pse p ON p.pse_id = a.pse_id WHERE a.confiar_id = ?");
            $capStmt->execute([$confiarId]);
            $asignadoTotal = (float)($capStmt->fetch()['asignado_total'] ?? 0);
            $pse = $this->getPseById($pseId);
            $pseValor = (float)($pse['valor'] ?? 0);
            $capacidadRestante = (float)$confiar['valor_consignacion'] - $asignadoTotal;
            if ($pseValor > $capacidadRestante) {
                throw new Exception('Este Confiar ya alcanzó su capacidad (suma de PSE asignados >= valor consignación)');
            }

            $stmtSel = $this->conn->prepare("SELECT * FROM banco_asignacion_pse WHERE pse_id = ? ORDER BY fecha_validacion DESC LIMIT 1");
            $stmtSel->execute([$pseId]);
            $prev = $stmtSel->fetch();

            if ($prev && $prev['confiar_id'] === $confiarId) {
                $this->conn->commit();
                return ['success' => true, 'message' => 'Asignación ya existente'];
            }

            $stmtDel = $this->conn->prepare("DELETE FROM banco_asignacion_pse WHERE pse_id = ?");
            $stmtDel->execute([$pseId]);

            $stmtIns = $this->conn->prepare("INSERT INTO banco_asignacion_pse (pse_id, confiar_id, tipo_asignacion) VALUES (?, ?, 'manual')");
            $stmtIns->execute([$pseId, $confiarId]);

            $this->conn->commit();
            return ['success' => true, 'replaced' => (bool)$prev, 'previous' => $prev];
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function removeAssignment($pseId) {
        $stmt = $this->conn->prepare("DELETE FROM banco_asignacion_pse WHERE pse_id = ?");
        $ok = $stmt->execute([$pseId]);
        return ['success' => $ok];
    }
}
