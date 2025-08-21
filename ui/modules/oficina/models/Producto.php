<?php
require_once __DIR__ . '/../../../config/database.php';

class Producto {
    private $conn;

    public function __construct() {
        $this->conn = getConnection();
    }

    public function getProductos($page = 1, $limit = 20, $search = '', $estado = '') {
        try {
            $offset = ($page - 1) * $limit;
            $where = [];
            $params = [];

            if (!empty($search)) {
                $where[] = "(nombre LIKE ? OR descripcion LIKE ?)";
                $like = "%$search%";
                $params[] = $like; $params[] = $like;
            }
            if ($estado !== '') {
                if ($estado === 'activo') { $where[] = "estado_activo = TRUE"; }
                if ($estado === 'inactivo') { $where[] = "estado_activo = FALSE"; }
            }

            $whereClause = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));

            $sql = "SELECT id, nombre, descripcion, parametros, valor_minimo, valor_maximo, prioridad, estado_activo, fecha_creacion, fecha_actualizacion
                    FROM control_productos
                    $whereClause
                    ORDER BY prioridad ASC, nombre ASC
                    LIMIT ? OFFSET ?";
            $paramsExec = array_merge($params, [$limit, $offset]);
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($paramsExec);
            $rows = $stmt->fetchAll();

            $countSql = "SELECT COUNT(*) as total FROM control_productos $whereClause";
            $countStmt = $this->conn->prepare($countSql);
            $countStmt->execute($params);
            $total = (int)($countStmt->fetch()['total'] ?? 0);

            return [
                'productos' => $rows,
                'total' => $total,
                'pages' => $limit > 0 ? (int)ceil($total / $limit) : 1,
                'current_page' => $page
            ];
        } catch (Exception $e) {
            error_log('Producto::getProductos error: ' . $e->getMessage());
            return ['productos' => [], 'total' => 0, 'pages' => 1, 'current_page' => 1];
        }
    }

    public function getById($id) {
        try {
            $stmt = $this->conn->prepare("SELECT id, nombre, descripcion, parametros, valor_minimo, valor_maximo, prioridad, estado_activo, fecha_creacion, fecha_actualizacion FROM control_productos WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log('Producto::getById error: ' . $e->getMessage());
            return false;
        }
    }

    public function create($nombre, $descripcion, $parametros, $valor_minimo, $valor_maximo, $prioridad = 100, $estado_activo = true) {
        try {
            $estado = $estado_activo ? 1 : 0;
            $stmt = $this->conn->prepare("INSERT INTO control_productos (nombre, descripcion, parametros, valor_minimo, valor_maximo, prioridad, estado_activo) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $ok = $stmt->execute([$nombre, $descripcion, $parametros, $valor_minimo, $valor_maximo, $prioridad, $estado]);
            if ($ok) {
                return ['success' => true, 'id' => $this->conn->lastInsertId(), 'message' => 'Producto creado'];
            }
            return ['success' => false, 'message' => 'No se pudo crear el producto'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function update($id, $nombre, $descripcion, $parametros, $valor_minimo, $valor_maximo, $prioridad, $estado_activo) {
        try {
            $estado = $estado_activo ? 1 : 0;
            $stmt = $this->conn->prepare("UPDATE control_productos SET nombre = ?, descripcion = ?, parametros = ?, valor_minimo = ?, valor_maximo = ?, prioridad = ?, estado_activo = ?, fecha_actualizacion = CURRENT_TIMESTAMP WHERE id = ?");
            $ok = $stmt->execute([$nombre, $descripcion, $parametros, $valor_minimo, $valor_maximo, $prioridad, $estado, $id]);
            if ($ok) { return ['success' => true, 'message' => 'Producto actualizado']; }
            return ['success' => false, 'message' => 'No se pudo actualizar'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function cambiarEstado($id, $estado) {
        try {
            $estadoInt = $estado ? 1 : 0;
            $stmt = $this->conn->prepare("UPDATE control_productos SET estado_activo = ?, fecha_actualizacion = CURRENT_TIMESTAMP WHERE id = ?");
            $ok = $stmt->execute([$estadoInt, $id]);
            if ($ok) { return ['success' => true, 'message' => 'Estado actualizado']; }
            return ['success' => false, 'message' => 'No se pudo actualizar el estado'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
