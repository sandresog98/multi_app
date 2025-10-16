<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../models/Logger.php';

class TasasProductos {
    private $conn;
    private $logger;
    
    public function __construct() {
        $this->conn = getConnection();
        $this->logger = new Logger();
    }
    
    /**
     * Obtener todas las tasas de productos con información del producto
     */
    public function listarTasas(): array {
        $sql = "SELECT tp.*, p.nombre AS producto_nombre
                FROM control_tasas_productos tp
                INNER JOIN control_productos p ON p.id = tp.producto_id
                ORDER BY tp.estado_activo DESC, tp.fecha_inicio DESC, p.nombre ASC";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener una tasa específica por ID
     */
    public function obtenerTasa(int $id): ?array {
        $sql = "SELECT tp.*, p.nombre AS producto_nombre
                FROM control_tasas_productos tp
                INNER JOIN control_productos p ON p.id = tp.producto_id
                WHERE tp.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Obtener productos activos para el dropdown
     */
    public function obtenerProductosActivos(): array {
        $sql = "SELECT id, nombre FROM control_productos 
                WHERE estado_activo = TRUE 
                ORDER BY nombre ASC";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Crear nueva tasa de producto
     */
    public function crearTasa(array $data, int $usuarioId): array {
        try {
            $this->conn->beginTransaction();
            
            $sql = "INSERT INTO control_tasas_productos 
                    (producto_id, fecha_inicio, fecha_fin, tasa, estado_activo, creado_por) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(1, $data['producto_id'], PDO::PARAM_INT);
            $stmt->bindValue(2, $data['fecha_inicio'], PDO::PARAM_STR);
            $stmt->bindValue(3, $data['fecha_fin'] ?: null, PDO::PARAM_STR);
            $stmt->bindValue(4, $data['tasa'], PDO::PARAM_STR);
            $stmt->bindValue(5, $data['estado_activo'] ?? true, PDO::PARAM_BOOL);
            $stmt->bindValue(6, $usuarioId, PDO::PARAM_INT);
            
            $stmt->execute();
            $tasaId = $this->conn->lastInsertId();
            
            $this->conn->commit();
            
            $this->logger->logCrear('tasas_productos', 'Tasa de producto creada', [
                'id' => $tasaId,
                'producto_id' => $data['producto_id'],
                'fecha_inicio' => $data['fecha_inicio']
            ]);
            
            return [
                'success' => true,
                'message' => 'Tasa de producto creada exitosamente',
                'id' => $tasaId
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            return [
                'success' => false,
                'message' => 'Error al crear tasa de producto: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Actualizar tasa de producto existente
     */
    public function actualizarTasa(int $id, array $data, int $usuarioId): array {
        try {
            $this->conn->beginTransaction();
            
            // Obtener datos actuales para el log
            $tasaActual = $this->obtenerTasa($id);
            if (!$tasaActual) {
                return [
                    'success' => false,
                    'message' => 'Tasa de producto no encontrada'
                ];
            }
            
            $sql = "UPDATE control_tasas_productos SET 
                    producto_id = ?, fecha_inicio = ?, fecha_fin = ?, 
                    tasa = ?, estado_activo = ?, actualizado_por = ?, 
                    fecha_actualizacion = CURRENT_TIMESTAMP
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(1, $data['producto_id'], PDO::PARAM_INT);
            $stmt->bindValue(2, $data['fecha_inicio'], PDO::PARAM_STR);
            $stmt->bindValue(3, $data['fecha_fin'] ?: null, PDO::PARAM_STR);
            $stmt->bindValue(4, $data['tasa'], PDO::PARAM_STR);
            $stmt->bindValue(5, $data['estado_activo'] ?? true, PDO::PARAM_BOOL);
            $stmt->bindValue(6, $usuarioId, PDO::PARAM_INT);
            $stmt->bindValue(7, $id, PDO::PARAM_INT);
            
            $stmt->execute();
            
            $this->conn->commit();
            
            $this->logger->logEditar('tasas_productos', 'Tasa de producto actualizada', $tasaActual, $data);
            
            return [
                'success' => true,
                'message' => 'Tasa de producto actualizada exitosamente'
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            return [
                'success' => false,
                'message' => 'Error al actualizar tasa de producto: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Eliminar tasa de producto (soft delete)
     */
    public function eliminarTasa(int $id, int $usuarioId): array {
        try {
            $this->conn->beginTransaction();
            
            // Obtener datos para el log
            $tasaActual = $this->obtenerTasa($id);
            if (!$tasaActual) {
                return [
                    'success' => false,
                    'message' => 'Tasa de producto no encontrada'
                ];
            }
            
            $sql = "UPDATE control_tasas_productos SET 
                    estado_activo = FALSE, actualizado_por = ?, 
                    fecha_actualizacion = CURRENT_TIMESTAMP
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(1, $usuarioId, PDO::PARAM_INT);
            $stmt->bindValue(2, $id, PDO::PARAM_INT);
            
            $stmt->execute();
            
            $this->conn->commit();
            
            $this->logger->logEliminar('tasas_productos', 'Tasa de producto eliminada', $tasaActual);
            
            return [
                'success' => true,
                'message' => 'Tasa de producto eliminada exitosamente'
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            return [
                'success' => false,
                'message' => 'Error al eliminar tasa de producto: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Validar datos de tasa de producto
     */
    public function validarDatos(array $data): array {
        $errores = [];
        
        if (empty($data['producto_id'])) {
            $errores[] = 'El producto es requerido';
        }
        
        if (empty($data['fecha_inicio'])) {
            $errores[] = 'La fecha de inicio es requerida';
        } elseif (!strtotime($data['fecha_inicio'])) {
            $errores[] = 'La fecha de inicio no es válida';
        }
        
        if (!empty($data['fecha_fin']) && !strtotime($data['fecha_fin'])) {
            $errores[] = 'La fecha de fin no es válida';
        }
        
        if (!empty($data['fecha_inicio']) && !empty($data['fecha_fin'])) {
            if (strtotime($data['fecha_fin']) < strtotime($data['fecha_inicio'])) {
                $errores[] = 'La fecha de fin debe ser posterior a la fecha de inicio';
            }
        }
        
        if (!isset($data['tasa']) || $data['tasa'] < 0) {
            $errores[] = 'La tasa debe ser mayor o igual a 0';
        }
        
        return $errores;
    }
    
    /**
     * Obtener tasas activas para un producto específico en una fecha
     */
    public function obtenerTasasActivasProducto(int $productoId, string $fecha = null): array {
        $fecha = $fecha ?: date('Y-m-d');
        
        $sql = "SELECT tp.*, p.nombre AS producto_nombre
                FROM control_tasas_productos tp
                INNER JOIN control_productos p ON p.id = tp.producto_id
                WHERE tp.producto_id = ? 
                AND tp.estado_activo = TRUE 
                AND tp.fecha_inicio <= ? 
                AND (tp.fecha_fin IS NULL OR tp.fecha_fin >= ?)
                ORDER BY tp.fecha_inicio DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(1, $productoId, PDO::PARAM_INT);
        $stmt->bindValue(2, $fecha, PDO::PARAM_STR);
        $stmt->bindValue(3, $fecha, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Verificar si ya existe una tasa activa para el mismo producto en el mismo período
     */
    public function verificarTasaExistente(int $productoId, string $fechaInicio, string $fechaFin = null, int $excluirId = null): bool {
        $sql = "SELECT COUNT(*) FROM control_tasas_productos 
                WHERE producto_id = ? 
                AND estado_activo = TRUE 
                AND fecha_inicio <= ? 
                AND (? IS NULL OR fecha_fin IS NULL OR fecha_fin >= ?)";
        
        $params = [$productoId, $fechaFin ?: $fechaInicio, $fechaFin, $fechaInicio];
        
        if ($excluirId) {
            $sql .= " AND id != ?";
            $params[] = $excluirId;
        }
        
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $i => $param) {
            $stmt->bindValue($i + 1, $param, PDO::PARAM_STR);
        }
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }
}
