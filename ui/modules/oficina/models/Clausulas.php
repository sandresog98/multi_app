<?php
require_once __DIR__ . '/../../../config/database.php';

class Clausulas {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    /**
     * Listar todas las cláusulas
     */
    public function listarClausulas(): array {
        $sql = "SELECT * FROM control_clausulas 
                ORDER BY estado_activo DESC, fecha_creacion DESC";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener una cláusula por ID
     */
    public function obtenerClausula(int $id): ?array {
        $sql = "SELECT * FROM control_clausulas WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Crear nueva cláusula
     */
    public function crearClausula(array $datos): array {
        try {
            // Validar datos requeridos
            if (empty($datos['nombre']) || empty($datos['descripcion']) || empty($datos['parametros'])) {
                return ['success' => false, 'message' => 'Todos los campos son obligatorios'];
            }
            
            $sql = "INSERT INTO control_clausulas (nombre, descripcion, parametros, requiere_archivo, estado_activo, creado_por) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            
            $result = $stmt->execute([
                $datos['nombre'],
                $datos['descripcion'],
                $datos['parametros'],
                $datos['requiere_archivo'] ?? false,
                $datos['estado_activo'] ?? true,
                $datos['creado_por'] ?? null
            ]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Cláusula creada exitosamente', 'id' => $this->conn->lastInsertId()];
            } else {
                return ['success' => false, 'message' => 'Error al crear la cláusula'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Actualizar cláusula
     */
    public function actualizarClausula(int $id, array $datos): array {
        try {
            // Validar datos requeridos
            if (empty($datos['nombre']) || empty($datos['descripcion']) || empty($datos['parametros'])) {
                return ['success' => false, 'message' => 'Todos los campos son obligatorios'];
            }
            
            $sql = "UPDATE control_clausulas 
                    SET nombre = ?, descripcion = ?, parametros = ?, requiere_archivo = ?, 
                        estado_activo = ?, actualizado_por = ?
                    WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            
            $result = $stmt->execute([
                $datos['nombre'],
                $datos['descripcion'],
                $datos['parametros'],
                $datos['requiere_archivo'] ?? false,
                $datos['estado_activo'] ?? true,
                $datos['actualizado_por'] ?? null,
                $id
            ]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Cláusula actualizada exitosamente'];
            } else {
                return ['success' => false, 'message' => 'Error al actualizar la cláusula'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Eliminar cláusula (soft delete)
     */
    public function eliminarClausula(int $id): array {
        try {
            $sql = "UPDATE control_clausulas SET estado_activo = FALSE WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([$id]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Cláusula eliminada exitosamente'];
            } else {
                return ['success' => false, 'message' => 'Error al eliminar la cláusula'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Obtener cláusulas activas para dropdown
     */
    public function obtenerClausulasActivas(): array {
        $sql = "SELECT id, nombre, requiere_archivo FROM control_clausulas 
                WHERE estado_activo = TRUE 
                ORDER BY nombre ASC";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener asignaciones de cláusulas de un asociado
     */
    public function obtenerAsignacionesAsociado(string $cedula): array {
        $sql = "SELECT aac.*, c.nombre AS clausula_nombre, c.descripcion AS clausula_descripcion,
                       c.requiere_archivo AS clausula_requiere_archivo
                FROM control_asignacion_asociado_clausula aac
                INNER JOIN control_clausulas c ON c.id = aac.clausula_id
                WHERE aac.asociado_cedula = ? AND aac.estado_activo = TRUE
                ORDER BY aac.fecha_inicio DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$cedula]);
        return $stmt->fetchAll();
    }
    
    /**
     * Asignar cláusula a asociado
     */
    public function asignarClausulaAsociado(array $datos): array {
        try {
            // Validar datos requeridos
            if (empty($datos['asociado_cedula']) || empty($datos['clausula_id']) || 
                empty($datos['monto_mensual']) || empty($datos['fecha_inicio']) || 
                empty($datos['meses_vigencia']) || empty($datos['parametros'])) {
                return ['success' => false, 'message' => 'Todos los campos son obligatorios'];
            }
            
            // Verificar si la cláusula requiere archivo
            $clausula = $this->obtenerClausula($datos['clausula_id']);
            if ($clausula && $clausula['requiere_archivo'] && empty($datos['archivo_ruta'])) {
                return ['success' => false, 'message' => 'Esta cláusula requiere un archivo adjunto'];
            }
            
            $sql = "INSERT INTO control_asignacion_asociado_clausula 
                    (asociado_cedula, clausula_id, monto_mensual, fecha_inicio, meses_vigencia, 
                     parametros, archivo_ruta, estado_activo, creado_por) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            
            $result = $stmt->execute([
                $datos['asociado_cedula'],
                $datos['clausula_id'],
                $datos['monto_mensual'],
                $datos['fecha_inicio'],
                $datos['meses_vigencia'],
                $datos['parametros'] ?? null,
                $datos['archivo_ruta'] ?? null,
                $datos['estado_activo'] ?? true,
                $datos['creado_por'] ?? null
            ]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Cláusula asignada exitosamente', 'id' => $this->conn->lastInsertId()];
            } else {
                return ['success' => false, 'message' => 'Error al asignar la cláusula'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Actualizar asignación de cláusula
     */
    public function actualizarAsignacionClausula(int $id, array $datos): array {
        try {
            // Validar datos requeridos
            if (empty($datos['monto_mensual']) || empty($datos['fecha_inicio']) || 
                empty($datos['meses_vigencia'])) {
                return ['success' => false, 'message' => 'Todos los campos son obligatorios'];
            }
            
            $sql = "UPDATE control_asignacion_asociado_clausula 
                    SET monto_mensual = ?, fecha_inicio = ?, meses_vigencia = ?, 
                        parametros = ?, archivo_ruta = ?, estado_activo = ?, actualizado_por = ?
                    WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            
            $result = $stmt->execute([
                $datos['monto_mensual'],
                $datos['fecha_inicio'],
                $datos['meses_vigencia'],
                $datos['parametros'] ?? null,
                $datos['archivo_ruta'] ?? null,
                $datos['estado_activo'] ?? true,
                $datos['actualizado_por'] ?? null,
                $id
            ]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Asignación actualizada exitosamente'];
            } else {
                return ['success' => false, 'message' => 'Error al actualizar la asignación'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Eliminar asignación de cláusula (soft delete)
     */
    public function eliminarAsignacionClausula(int $id): array {
        try {
            $sql = "UPDATE control_asignacion_asociado_clausula SET estado_activo = FALSE WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([$id]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Asignación eliminada exitosamente'];
            } else {
                return ['success' => false, 'message' => 'Error al eliminar la asignación'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}
