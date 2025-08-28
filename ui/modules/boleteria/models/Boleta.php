<?php
require_once __DIR__ . '/../../../config/database.php';

class Boleta {
    private $conn;

    public function __construct() {
        $this->conn = getConnection();
    }

    public function listar($page = 1, $limit = 20, $filters = [], $sortBy = 'id', $sortDir = 'DESC') {
        try {
            $offset = ($page - 1) * $limit;
            $where = [];
            $params = [];

            if (!empty($filters['categoria_id'])) { $where[] = 'b.categoria_id = ?'; $params[] = $filters['categoria_id']; }
            if ($filters['estado'] !== '' && $filters['estado'] !== null) { $where[] = 'b.estado = ?'; $params[] = $filters['estado']; }
            if (!empty($filters['serial'])) { $where[] = 'b.serial LIKE ?'; $params[] = '%' . $filters['serial'] . '%'; }
            if (!empty($filters['cedula'])) { $where[] = 'b.asociado_cedula LIKE ?'; $params[] = '%' . $filters['cedula'] . '%'; }
            if (!empty($filters['fecha_creacion_desde'])) { $where[] = 'DATE(b.fecha_creacion) >= ?'; $params[] = $filters['fecha_creacion_desde']; }
            if (!empty($filters['fecha_creacion_hasta'])) { $where[] = 'DATE(b.fecha_creacion) <= ?'; $params[] = $filters['fecha_creacion_hasta']; }
            if (!empty($filters['fecha_vendida_desde'])) { $where[] = 'DATE(b.fecha_vendida) >= ?'; $params[] = $filters['fecha_vendida_desde']; }
            if (!empty($filters['fecha_vendida_hasta'])) { $where[] = 'DATE(b.fecha_vendida) <= ?'; $params[] = $filters['fecha_vendida_hasta']; }

            $whereClause = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));

            $allowedSort = [
                'id' => 'b.id',
                'serial' => 'b.serial',
                'categoria' => 'c.nombre',
                'precio_compra' => 'b.precio_compra_snapshot',
                'precio_venta' => 'b.precio_venta_snapshot',
                'estado' => 'b.estado',
                'fecha_creacion' => 'b.fecha_creacion',
                'fecha_vendida' => 'b.fecha_vendida',
                'fecha_actualizacion' => 'b.fecha_actualizacion'
            ];
            $col = $allowedSort[$sortBy] ?? 'b.id';
            $dir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

            $sql = "SELECT b.id, b.categoria_id, c.nombre AS categoria_nombre, b.serial, b.precio_compra_snapshot, b.precio_venta_snapshot, b.estado, b.asociado_cedula, sa.nombre AS asociado_nombre, b.metodo_venta, b.comprobante, b.fecha_creacion, b.fecha_vendida, b.fecha_actualizacion, b.archivo_ruta
                    FROM boleteria_boletas b
                    LEFT JOIN boleteria_categoria c ON c.id = b.categoria_id
                    LEFT JOIN sifone_asociados sa ON sa.cedula = b.asociado_cedula
                    $whereClause
                    ORDER BY $col $dir
                    LIMIT ? OFFSET ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(array_merge($params, [$limit, $offset]));
            $rows = $stmt->fetchAll();

            $countSql = "SELECT COUNT(*) as total
                          FROM boleteria_boletas b
                          LEFT JOIN boleteria_categoria c ON c.id = b.categoria_id
                          $whereClause";
            $countStmt = $this->conn->prepare($countSql);
            $countStmt->execute($params);
            $total = (int)($countStmt->fetch()['total'] ?? 0);

            return [
                'items' => $rows,
                'total' => $total,
                'pages' => $limit > 0 ? (int)ceil($total / $limit) : 1,
                'current_page' => $page
            ];
        } catch (Exception $e) {
            error_log('Boleta::listar error: ' . $e->getMessage());
            return ['items' => [], 'total' => 0, 'pages' => 1, 'current_page' => 1];
        }
    }

    public function crear($categoriaId, $serial, $precioCompra, $precioVenta, $archivoRuta = null) {
        try {
            if ((int)$categoriaId <= 0) { return ['success' => false, 'message' => 'Categoría requerida']; }
            $serial = trim((string)$serial);
            if ($serial === '') { return ['success' => false, 'message' => 'Serial requerido']; }
            $precioCompra = (float)$precioCompra; $precioVenta = (float)$precioVenta;
            if ($precioCompra < 0 || $precioVenta < 0) { return ['success' => false, 'message' => 'Precios deben ser >= 0']; }

            // Unicidad de serial por categoría
            if ($this->existeSerial($categoriaId, $serial)) { return ['success' => false, 'message' => 'El serial ya existe en esta categoría']; }

            $stmt = $this->conn->prepare("INSERT INTO boleteria_boletas (categoria_id, serial, precio_compra_snapshot, precio_venta_snapshot, archivo_ruta) VALUES (?, ?, ?, ?, ?)");
            $ok = $stmt->execute([$categoriaId, $serial, $precioCompra, $precioVenta, $archivoRuta]);
            if ($ok) { return ['success' => true, 'id' => $this->conn->lastInsertId(), 'message' => 'Boleta creada']; }
            return ['success' => false, 'message' => 'No se pudo crear la boleta'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function existeSerial($categoriaId, $serial) {
        $stmt = $this->conn->prepare("SELECT 1 FROM boleteria_boletas WHERE categoria_id = ? AND serial = ? LIMIT 1");
        $stmt->execute([$categoriaId, $serial]);
        return (bool)$stmt->fetchColumn();
    }

    public function vender($id, $cedulaAsociado, $metodoVenta = null, $comprobante = null) {
        try {
            $cedula = trim((string)$cedulaAsociado);
            if ($cedula === '') { return ['success' => false, 'message' => 'Cédula requerida']; }
            $metodo = $metodoVenta ? (string)$metodoVenta : null;
            $comp = $comprobante !== null ? (string)$comprobante : null;
            $permitidos = ['Directa','Incentivos','Credito'];
            if ($metodo === null || !in_array($metodo, $permitidos, true)) {
                return ['success' => false, 'message' => 'Método de venta inválido'];
            }
            $stmt = $this->conn->prepare("UPDATE boleteria_boletas SET estado = 'vendida', asociado_cedula = ?, fecha_vendida = CURRENT_TIMESTAMP, fecha_actualizacion = CURRENT_TIMESTAMP, metodo_venta = ?, comprobante = ? WHERE id = ? AND estado = 'disponible'");
            $ok = $stmt->execute([$cedula, $metodo, $comp, $id]);
            if ($ok && $stmt->rowCount() > 0) { return ['success' => true, 'message' => 'Boleta vendida']; }
            return ['success' => false, 'message' => 'No se pudo vender (ya vendida/anulada o no existe)'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function anular($id) {
        try {
            $stmt = $this->conn->prepare("UPDATE boleteria_boletas SET estado = 'anulada', fecha_actualizacion = CURRENT_TIMESTAMP WHERE id = ? AND estado <> 'anulada'");
            $ok = $stmt->execute([$id]);
            if ($ok && $stmt->rowCount() > 0) { return ['success' => true, 'message' => 'Boleta anulada']; }
            return ['success' => false, 'message' => 'No se pudo anular'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function deshacerVenta($id) {
        try {
            $stmt = $this->conn->prepare("UPDATE boleteria_boletas SET estado = 'disponible', asociado_cedula = NULL, fecha_vendida = NULL, fecha_actualizacion = CURRENT_TIMESTAMP WHERE id = ? AND estado = 'vendida'");
            $ok = $stmt->execute([$id]);
            if ($ok && $stmt->rowCount() > 0) { return ['success' => true, 'message' => 'Venta deshecha']; }
            return ['success' => false, 'message' => 'No se pudo deshacer (no estaba vendida)'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function desanular($id) {
        try {
            $stmt = $this->conn->prepare("UPDATE boleteria_boletas SET estado = 'disponible', fecha_actualizacion = CURRENT_TIMESTAMP WHERE id = ? AND estado = 'anulada'");
            $ok = $stmt->execute([$id]);
            if ($ok && $stmt->rowCount() > 0) { return ['success' => true, 'message' => 'Boleta desanulada']; }
            return ['success' => false, 'message' => 'No se pudo desanular (no estaba anulada)'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getResumenKpis() {
        try {
            $kpis = [];
            $sql = "SELECT 
                        COUNT(*) total,
                        SUM(estado='disponible') disponibles,
                        SUM(estado='vendida') vendidas,
                        SUM(estado='anulada') anuladas,
                        COALESCE(SUM(CASE WHEN estado='vendida' THEN precio_venta_snapshot END),0) as ingreso_bruto,
                        COALESCE(SUM(CASE WHEN estado='vendida' THEN precio_compra_snapshot END),0) as costo_vendido
                    FROM boleteria_boletas";
            $kpis = $this->conn->query($sql)->fetch(PDO::FETCH_ASSOC);

            $porCategoria = $this->conn->query("SELECT c.nombre AS categoria, 
                                                      COUNT(*) total,
                                                      SUM(b.estado='vendida') vendidas
                                               FROM boleteria_boletas b 
                                               LEFT JOIN boleteria_categoria c ON c.id=b.categoria_id
                                               GROUP BY c.nombre
                                               ORDER BY total DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

            $vendidasDia = $this->conn->query("SELECT DATE(fecha_vendida) fecha, COUNT(*) cantidad
                                               FROM boleteria_boletas
                                               WHERE estado='vendida' AND fecha_vendida IS NOT NULL
                                               GROUP BY DATE(fecha_vendida)
                                               ORDER BY fecha DESC LIMIT 14")->fetchAll(PDO::FETCH_ASSOC);

            return [
                'kpis' => $kpis,
                'por_categoria' => $porCategoria,
                'vendidas_dia' => array_reverse($vendidasDia)
            ];
        } catch (Exception $e) {
            return [ 'kpis' => [], 'por_categoria' => [], 'vendidas_dia' => [] ];
        }
    }
}


