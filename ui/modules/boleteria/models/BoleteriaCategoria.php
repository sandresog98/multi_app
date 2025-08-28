<?php
require_once __DIR__ . '/../../../config/database.php';

class BoleteriaCategoria {
    private $conn;

    public function __construct() {
        $this->conn = getConnection();
    }

    public function listar($page = 1, $limit = 20, $search = '', $estado = '', $sortBy = 'nombre', $sortDir = 'ASC') {
        try {
            $offset = ($page - 1) * $limit;
            $where = [];
            $params = [];

            if ($search !== '') {
                $where[] = "(nombre LIKE ? OR descripcion LIKE ?)";
                $like = "%$search%";
                $params[] = $like; $params[] = $like;
            }
            if ($estado !== '') {
                $where[] = "estado = ?";
                $params[] = $estado;
            }

            $whereClause = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));

            $allowedSort = [
                'nombre' => 'nombre',
                'precio_compra' => 'precio_compra',
                'precio_venta' => 'precio_venta',
                'estado' => 'estado',
                'fecha_creacion' => 'fecha_creacion',
                'fecha_actualizacion' => 'fecha_actualizacion'
            ];
            $col = $allowedSort[$sortBy] ?? 'nombre';
            $dir = strtoupper($sortDir) === 'DESC' ? 'DESC' : 'ASC';

            $sql = "SELECT id, nombre, precio_compra, precio_venta, descripcion, estado, fecha_creacion, fecha_actualizacion
                    FROM boleteria_categoria
                    $whereClause
                    ORDER BY $col $dir
                    LIMIT ? OFFSET ?";
            $stmt = $this->conn->prepare($sql);
            $paramsExec = array_merge($params, [$limit, $offset]);
            $stmt->execute($paramsExec);
            $rows = $stmt->fetchAll();

            $countSql = "SELECT COUNT(*) as total FROM boleteria_categoria $whereClause";
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
            error_log('BoleteriaCategoria::listar error: ' . $e->getMessage());
            return ['items' => [], 'total' => 0, 'pages' => 1, 'current_page' => 1];
        }
    }

    public function crear($nombre, $precioCompra, $precioVenta, $descripcion = null, $estado = 'activo') {
        try {
            if ($nombre === '') { return ['success' => false, 'message' => 'Nombre requerido']; }
            if (!in_array($estado, ['activo','inactivo'], true)) { return ['success' => false, 'message' => 'Estado inválido']; }
            $precioCompra = (float)$precioCompra; $precioVenta = (float)$precioVenta;
            if ($precioCompra < 0 || $precioVenta < 0) { return ['success' => false, 'message' => 'Precios deben ser >= 0']; }

            // Unicidad por nombre
            $check = $this->conn->prepare("SELECT id FROM boleteria_categoria WHERE nombre = ? LIMIT 1");
            $check->execute([$nombre]);
            if ($check->fetch()) { return ['success' => false, 'message' => 'La categoría ya existe']; }

            $stmt = $this->conn->prepare("INSERT INTO boleteria_categoria (nombre, precio_compra, precio_venta, descripcion, estado) VALUES (?, ?, ?, ?, ?)");
            $ok = $stmt->execute([$nombre, $precioCompra, $precioVenta, $descripcion, $estado]);
            if ($ok) {
                return ['success' => true, 'id' => $this->conn->lastInsertId(), 'message' => 'Categoría creada'];
            }
            return ['success' => false, 'message' => 'No se pudo crear la categoría'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function actualizar($id, $nombre, $precioCompra, $precioVenta, $descripcion, $estado) {
        try {
            if ($nombre === '') { return ['success' => false, 'message' => 'Nombre requerido']; }
            if (!in_array($estado, ['activo','inactivo'], true)) { return ['success' => false, 'message' => 'Estado inválido']; }
            $precioCompra = (float)$precioCompra; $precioVenta = (float)$precioVenta;
            if ($precioCompra < 0 || $precioVenta < 0) { return ['success' => false, 'message' => 'Precios deben ser >= 0']; }

            // Validar unicidad de nombre excluyendo el mismo id
            $check = $this->conn->prepare("SELECT id FROM boleteria_categoria WHERE nombre = ? AND id <> ? LIMIT 1");
            $check->execute([$nombre, $id]);
            if ($check->fetch()) { return ['success' => false, 'message' => 'El nombre ya está en uso']; }

            $stmt = $this->conn->prepare("UPDATE boleteria_categoria SET nombre = ?, precio_compra = ?, precio_venta = ?, descripcion = ?, estado = ?, fecha_actualizacion = CURRENT_TIMESTAMP WHERE id = ?");
            $ok = $stmt->execute([$nombre, $precioCompra, $precioVenta, $descripcion, $estado, $id]);
            if ($ok) { return ['success' => true, 'message' => 'Categoría actualizada']; }
            return ['success' => false, 'message' => 'No se pudo actualizar'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getById($id) {
        try {
            $stmt = $this->conn->prepare("SELECT id, nombre, precio_compra, precio_venta, descripcion, estado, fecha_creacion, fecha_actualizacion FROM boleteria_categoria WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
}


