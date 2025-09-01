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
            if (!empty($filters['fecha_vencimiento_desde'])) { $where[] = 'DATE(b.fecha_vencimiento) >= ?'; $params[] = $filters['fecha_vencimiento_desde']; }
            if (!empty($filters['fecha_vencimiento_hasta'])) { $where[] = 'DATE(b.fecha_vencimiento) <= ?'; $params[] = $filters['fecha_vencimiento_hasta']; }

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

            $sql = "SELECT b.id, b.categoria_id, c.nombre AS categoria_nombre, b.serial, b.precio_compra_snapshot, b.precio_venta_snapshot, b.estado, b.asociado_cedula, sa.nombre AS asociado_nombre, b.metodo_venta, b.comprobante, b.fecha_creacion, b.fecha_vendida, b.fecha_contabilizacion, b.fecha_vencimiento, b.fecha_actualizacion, b.archivo_ruta,
                           uc.nombre_completo AS creado_por_nombre, uv.nombre_completo AS vendido_por_nombre, uco.nombre_completo AS contabilizado_por_nombre
                    FROM boleteria_boletas b
                    LEFT JOIN boleteria_categoria c ON c.id = b.categoria_id
                    LEFT JOIN sifone_asociados sa ON sa.cedula = b.asociado_cedula
                    LEFT JOIN control_usuarios uc ON uc.id = b.creado_por
                    LEFT JOIN control_usuarios uv ON uv.id = b.vendido_por
                    LEFT JOIN control_usuarios uco ON uco.id = b.contabilizado_por
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

    public function crear($categoriaId, $serial, $precioCompra, $precioVenta, $archivoRuta = null, $fechaVencimiento = null, $usuarioId = null) {
        try {
            if ((int)$categoriaId <= 0) { return ['success' => false, 'message' => 'Categoría requerida']; }
            $serial = trim((string)$serial);
            if ($serial === '') { return ['success' => false, 'message' => 'Serial requerido']; }
            $precioCompra = (float)$precioCompra; $precioVenta = (float)$precioVenta;
            if ($precioCompra < 0 || $precioVenta < 0) { return ['success' => false, 'message' => 'Precios deben ser >= 0']; }

            // Unicidad de serial por categoría
            if ($this->existeSerial($categoriaId, $serial)) { return ['success' => false, 'message' => 'El serial ya existe en esta categoría']; }

            $stmt = $this->conn->prepare("INSERT INTO boleteria_boletas (categoria_id, serial, precio_compra_snapshot, precio_venta_snapshot, archivo_ruta, fecha_vencimiento, creado_por) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $ok = $stmt->execute([$categoriaId, $serial, $precioCompra, $precioVenta, $archivoRuta, $fechaVencimiento, $usuarioId]);
            if ($ok) { 
                $boletaId = $this->conn->lastInsertId();
                // Registrar evento
                $this->registrarEvento($boletaId, $usuarioId, 'crear', null, 'disponible', 'Boleta creada');
                return ['success' => true, 'id' => $boletaId, 'message' => 'Boleta creada']; 
            }
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

    public function vender($id, $cedulaAsociado, $metodoVenta = 'credito', $usuarioId = null) {
        try {
            $cedula = trim((string)$cedulaAsociado);
            if ($cedula === '') { return ['success' => false, 'message' => 'Cédula requerida']; }
            
            // Solo permitir los dos métodos especificados
            $metodo = in_array($metodoVenta, ['credito', 'regalo_cooperativa']) ? $metodoVenta : 'credito';
            
            $stmt = $this->conn->prepare("UPDATE boleteria_boletas SET estado = 'vendida', asociado_cedula = ?, fecha_vendida = CURRENT_TIMESTAMP, fecha_actualizacion = CURRENT_TIMESTAMP, metodo_venta = ?, vendido_por = ? WHERE id = ? AND estado = 'disponible'");
            $ok = $stmt->execute([$cedula, $metodo, $usuarioId, $id]);
            if ($ok && $stmt->rowCount() > 0) { 
                // Registrar evento
                $this->registrarEvento($id, $usuarioId, 'vender', 'disponible', 'vendida', 'Boleta vendida a ' . $cedula . ' por método ' . $metodo);
                return ['success' => true, 'message' => 'Boleta vendida']; 
            }
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

    public function contabilizar($id, $comprobante, $usuarioId = null) {
        try {
            if (empty($comprobante)) {
                return ['success' => false, 'message' => 'Comprobante requerido'];
            }

            // Verificar que la boleta esté vendida
            $stmt = $this->conn->prepare("SELECT estado FROM boleteria_boletas WHERE id = ?");
            $stmt->execute([$id]);
            $boleta = $stmt->fetch();
            
            if (!$boleta) {
                return ['success' => false, 'message' => 'Boleta no encontrada'];
            }
            
            if ($boleta['estado'] !== 'vendida') {
                return ['success' => false, 'message' => 'Solo se pueden contabilizar boletas vendidas'];
            }

            $stmt = $this->conn->prepare("UPDATE boleteria_boletas SET estado = 'contabilizada', comprobante = ?, fecha_contabilizacion = CURRENT_TIMESTAMP, fecha_actualizacion = CURRENT_TIMESTAMP, contabilizado_por = ? WHERE id = ? AND estado = 'vendida'");
            $ok = $stmt->execute([$comprobante, $usuarioId, $id]);
            if ($ok && $stmt->rowCount() > 0) { 
                // Registrar evento
                $this->registrarEvento($id, $usuarioId, 'contabilizar', 'vendida', 'contabilizada', 'Boleta contabilizada con comprobante: ' . $comprobante);
                return ['success' => true, 'message' => 'Boleta contabilizada']; 
            }
            return ['success' => false, 'message' => 'No se pudo contabilizar'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function registrarEvento($boletaId, $usuarioId, $accion, $estadoAnterior, $estadoNuevo, $detalle = null) {
        try {
            $stmt = $this->conn->prepare("INSERT INTO boleteria_eventos (boleta_id, usuario_id, accion, estado_anterior, estado_nuevo, detalle) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$boletaId, $usuarioId, $accion, $estadoAnterior, $estadoNuevo, $detalle]);
        } catch (Exception $e) {
            error_log('Error al registrar evento: ' . $e->getMessage());
        }
    }

    public function obtenerEventos($boletaId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT e.*, u.nombre_completo as usuario_nombre 
                FROM boleteria_eventos e 
                LEFT JOIN control_usuarios u ON u.id = e.usuario_id 
                WHERE e.boleta_id = ? 
                ORDER BY e.fecha DESC
            ");
            $stmt->execute([$boletaId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getResumenKpis() {
        try {
            $kpis = $this->conn->query("SELECT 
                        COUNT(*) AS creadas,
                        SUM(estado='contabilizada') AS contabilizadas,
                        SUM(estado='disponible') AS disponibles,
                        SUM(estado='anulada') AS anuladas
                    FROM boleteria_boletas")->fetch(PDO::FETCH_ASSOC);

            $disponiblesCat = $this->conn->query("SELECT COALESCE(c.nombre,'Sin categoría') AS categoria, COUNT(*) AS cantidad
                FROM boleteria_boletas b
                LEFT JOIN boleteria_categoria c ON c.id=b.categoria_id
                WHERE b.estado='disponible'
                GROUP BY c.nombre
                ORDER BY cantidad DESC")->fetchAll(PDO::FETCH_ASSOC);

            $vendidasCat = $this->conn->query("SELECT COALESCE(c.nombre,'Sin categoría') AS categoria, COUNT(*) AS cantidad
                FROM boleteria_boletas b
                LEFT JOIN boleteria_categoria c ON c.id=b.categoria_id
                WHERE b.estado IN ('vendida','contabilizada')
                GROUP BY c.nombre
                ORDER BY cantidad DESC")->fetchAll(PDO::FETCH_ASSOC);

            $topCompras1y = $this->topAsociadosCompras(365, 10);
            $topComprasAll = $this->topAsociadosCompras(null, 10);

            return [
                'kpis' => $kpis,
                'disponibles_cat' => $disponiblesCat,
                'vendidas_cat' => $vendidasCat,
                'top_1y' => $topCompras1y,
                'top_all' => $topComprasAll,
            ];
        } catch (Exception $e) {
            return [ 'kpis' => [], 'disponibles_cat' => [], 'vendidas_cat' => [], 'top_1y' => [], 'top_all' => [] ];
        }
    }

    public function topAsociadosCompras($dias = null, $limit = 10) {
        $limit = max(1, (int)$limit);
        $params = [];
        $where = "WHERE b.estado IN ('vendida','contabilizada') AND b.asociado_cedula IS NOT NULL";
        if ($dias !== null) {
            $where .= " AND b.fecha_vendida >= DATE_SUB(NOW(), INTERVAL ? DAY)";
            $params[] = (int)$dias;
        }
        $sql = "SELECT b.asociado_cedula AS cedula, COALESCE(sa.nombre, b.asociado_cedula) AS nombre, COUNT(*) AS cantidad
                FROM boleteria_boletas b
                LEFT JOIN sifone_asociados sa ON sa.cedula = b.asociado_cedula
                $where
                GROUP BY b.asociado_cedula, sa.nombre
                ORDER BY cantidad DESC
                LIMIT $limit";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}


