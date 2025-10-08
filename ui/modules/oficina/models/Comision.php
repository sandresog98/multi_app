<?php
require_once __DIR__ . '/../../../config/database.php';

class Comision {
    private $conn;

    public function __construct() {
        $this->conn = getConnection();
    }

    public function crear($asociadoInicial, $asociadoReferido, $fechaComision, $valorGanado, $observaciones, $usuarioId = null) {
        try {
            if (!$asociadoInicial || !$asociadoReferido) {
                return ['success' => false, 'message' => 'Debe indicar los dos asociados.'];
            }
            if ($asociadoInicial === $asociadoReferido) {
                return ['success' => false, 'message' => 'El asociado inicial y el referido no pueden ser el mismo.'];
            }
            if (!$fechaComision) {
                return ['success' => false, 'message' => 'La fecha de comisión es obligatoria.'];
            }
            $valor = (float)$valorGanado;
            if (!is_finite($valor) || $valor <= 0) {
                return ['success' => false, 'message' => 'El valor ganado debe ser mayor que 0.'];
            }

            $stmt = $this->conn->prepare("INSERT INTO control_comisiones (
                asociado_inicial_cedula, asociado_referido_cedula, fecha_comision, valor_ganado, observaciones, creado_por
            ) VALUES (?, ?, ?, ?, ?, ?)");
            $ok = $stmt->execute([
                $asociadoInicial,
                $asociadoReferido,
                $fechaComision,
                $valor,
                $observaciones !== '' ? $observaciones : null,
                $usuarioId
            ]);
            if (!$ok) { return ['success'=>false,'message'=>'No se pudo guardar la comisión']; }
            $id = (int)$this->conn->lastInsertId();
            return ['success'=>true,'id'=>$id];
        } catch (Throwable $e) {
            return ['success'=>false,'message'=>'Error al guardar: '.$e->getMessage()];
        }
    }

    public function listar($page = 1, $limit = 20, $filtros = []) {
        try {
            $offset = ($page - 1) * $limit;
            $where = [];
            $params = [];
            
            if (!empty($filtros['asociado_inicial'])) {
                $where[] = "c.asociado_inicial_cedula LIKE ?";
                $params[] = '%' . $filtros['asociado_inicial'] . '%';
            }
            if (!empty($filtros['asociado_referido'])) {
                $where[] = "c.asociado_referido_cedula LIKE ?";
                $params[] = '%' . $filtros['asociado_referido'] . '%';
            }
            if (!empty($filtros['fecha_desde'])) {
                $where[] = "c.fecha_comision >= ?";
                $params[] = $filtros['fecha_desde'];
            }
            if (!empty($filtros['fecha_hasta'])) {
                $where[] = "c.fecha_comision <= ?";
                $params[] = $filtros['fecha_hasta'];
            }

            $whereClause = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));
            
            $sql = "SELECT c.*, 
                           ai.nombre as inicial_nombre, 
                           ar.nombre as referido_nombre,
                           u.nombre_completo as creado_por_nombre
                    FROM control_comisiones c
                    LEFT JOIN sifone_asociados ai ON c.asociado_inicial_cedula = ai.cedula
                    LEFT JOIN sifone_asociados ar ON c.asociado_referido_cedula = ar.cedula
                    LEFT JOIN control_usuarios u ON c.creado_por = u.id
                    $whereClause
                    ORDER BY c.fecha_comision DESC, c.id DESC
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(array_merge($params, [$limit, $offset]));
            $rows = $stmt->fetchAll();

            $countSql = "SELECT COUNT(*) as total FROM control_comisiones c $whereClause";
            $countStmt = $this->conn->prepare($countSql);
            $countStmt->execute($params);
            $total = (int)($countStmt->fetch()['total'] ?? 0);

            return ['comisiones'=>$rows,'total'=>$total,'pages'=>$limit?ceil($total/$limit):1,'current_page'=>$page];
        } catch (Exception $e) { 
            return ['comisiones'=>[],'total'=>0,'pages'=>1,'current_page'=>1]; 
        }
    }

    public function editar($id, $asociadoInicial, $asociadoReferido, $fechaComision, $valorGanado, $observaciones) {
        try {
            if (!$asociadoInicial || !$asociadoReferido) {
                return ['success' => false, 'message' => 'Debe indicar los dos asociados.'];
            }
            if ($asociadoInicial === $asociadoReferido) {
                return ['success' => false, 'message' => 'El asociado inicial y el referido no pueden ser el mismo.'];
            }
            if (!$fechaComision) {
                return ['success' => false, 'message' => 'La fecha de comisión es obligatoria.'];
            }
            $valor = (float)$valorGanado;
            if (!is_finite($valor) || $valor <= 0) {
                return ['success' => false, 'message' => 'El valor ganado debe ser mayor que 0.'];
            }

            $stmt = $this->conn->prepare("UPDATE control_comisiones SET 
                asociado_inicial_cedula = ?, 
                asociado_referido_cedula = ?, 
                fecha_comision = ?, 
                valor_ganado = ?, 
                observaciones = ?
                WHERE id = ?");
            $ok = $stmt->execute([
                $asociadoInicial,
                $asociadoReferido,
                $fechaComision,
                $valor,
                $observaciones !== '' ? $observaciones : null,
                $id
            ]);
            if (!$ok) { return ['success'=>false,'message'=>'No se pudo actualizar la comisión']; }
            return ['success'=>true];
        } catch (Throwable $e) {
            return ['success'=>false,'message'=>'Error al actualizar: '.$e->getMessage()];
        }
    }

    public function eliminar($id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM control_comisiones WHERE id = ?");
            $ok = $stmt->execute([$id]);
            if (!$ok) { return ['success'=>false,'message'=>'No se pudo eliminar la comisión']; }
            return ['success'=>true];
        } catch (Throwable $e) {
            return ['success'=>false,'message'=>'Error al eliminar: '.$e->getMessage()];
        }
    }

    public function obtenerPorId($id) {
        try {
            $sql = "SELECT c.*, 
                           ai.nombre as inicial_nombre, 
                           ar.nombre as referido_nombre
                    FROM control_comisiones c
                    LEFT JOIN sifone_asociados ai ON c.asociado_inicial_cedula = ai.cedula
                    LEFT JOIN sifone_asociados ar ON c.asociado_referido_cedula = ar.cedula
                    WHERE c.id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
}
?>


