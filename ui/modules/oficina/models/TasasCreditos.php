<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../models/Logger.php';

class TasasCreditos {
    private $conn;
    private $logger;
    
    public function __construct() {
        $this->conn = getConnection();
        $this->logger = new Logger();
    }
    
    /**
     * Obtener todas las tasas de créditos
     */
    public function listarTasas(): array {
        $sql = "SELECT * FROM control_tasas_creditos 
                ORDER BY estado_activo DESC, fecha_inicio DESC, nombre_credito ASC";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener una tasa específica por ID
     */
    public function obtenerTasa(int $id): ?array {
        $sql = "SELECT * FROM control_tasas_creditos WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Crear nueva tasa de crédito
     */
    public function crearTasa(array $data, int $usuarioId): array {
        try {
            $this->conn->beginTransaction();
            
            $sql = "INSERT INTO control_tasas_creditos 
                    (nombre_credito, fecha_inicio, fecha_fin, limite_meses, 
                     tasa, seguro_vida, seguro_deudores, estado_activo, creado_por) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(1, $data['nombre_credito'], PDO::PARAM_STR);
            $stmt->bindValue(2, $data['fecha_inicio'], PDO::PARAM_STR);
            $stmt->bindValue(3, $data['fecha_fin'] ?: null, PDO::PARAM_STR);
            $stmt->bindValue(4, $data['limite_meses'], PDO::PARAM_INT);
            $stmt->bindValue(5, $data['tasa'], PDO::PARAM_STR);
            $stmt->bindValue(6, $data['seguro_vida'], PDO::PARAM_STR);
            $stmt->bindValue(7, $data['seguro_deudores'], PDO::PARAM_STR);
            $stmt->bindValue(8, $data['estado_activo'] ?? true, PDO::PARAM_BOOL);
            $stmt->bindValue(9, $usuarioId, PDO::PARAM_INT);
            
            $stmt->execute();
            $tasaId = $this->conn->lastInsertId();
            
            $this->conn->commit();
            
            $this->logger->logCrear('tasas_creditos', 'Tasa de crédito creada', [
                'id' => $tasaId,
                'nombre_credito' => $data['nombre_credito'],
                'fecha_inicio' => $data['fecha_inicio']
            ]);
            
            return [
                'success' => true,
                'message' => 'Tasa de crédito creada exitosamente',
                'id' => $tasaId
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            return [
                'success' => false,
                'message' => 'Error al crear tasa de crédito: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Actualizar tasa de crédito existente
     */
    public function actualizarTasa(int $id, array $data, int $usuarioId): array {
        try {
            $this->conn->beginTransaction();
            
            // Obtener datos actuales para el log
            $tasaActual = $this->obtenerTasa($id);
            if (!$tasaActual) {
                return [
                    'success' => false,
                    'message' => 'Tasa de crédito no encontrada'
                ];
            }
            
            $sql = "UPDATE control_tasas_creditos SET 
                    nombre_credito = ?, fecha_inicio = ?, fecha_fin = ?, 
                    limite_meses = ?, tasa = ?, seguro_vida = ?, seguro_deudores = ?, 
                    estado_activo = ?, actualizado_por = ?, 
                    fecha_actualizacion = CURRENT_TIMESTAMP
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(1, $data['nombre_credito'], PDO::PARAM_STR);
            $stmt->bindValue(2, $data['fecha_inicio'], PDO::PARAM_STR);
            $stmt->bindValue(3, $data['fecha_fin'] ?: null, PDO::PARAM_STR);
            $stmt->bindValue(4, $data['limite_meses'], PDO::PARAM_INT);
            $stmt->bindValue(5, $data['tasa'], PDO::PARAM_STR);
            $stmt->bindValue(6, $data['seguro_vida'], PDO::PARAM_STR);
            $stmt->bindValue(7, $data['seguro_deudores'], PDO::PARAM_STR);
            $stmt->bindValue(8, $data['estado_activo'] ?? true, PDO::PARAM_BOOL);
            $stmt->bindValue(9, $usuarioId, PDO::PARAM_INT);
            $stmt->bindValue(10, $id, PDO::PARAM_INT);
            
            $stmt->execute();
            
            $this->conn->commit();
            
            $this->logger->logEditar('tasas_creditos', 'Tasa de crédito actualizada', $tasaActual, $data);
            
            return [
                'success' => true,
                'message' => 'Tasa de crédito actualizada exitosamente'
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            return [
                'success' => false,
                'message' => 'Error al actualizar tasa de crédito: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Eliminar tasa de crédito (hard delete)
     */
    public function eliminarTasa(int $id, int $usuarioId): array {
        try {
            $this->conn->beginTransaction();
            
            // Obtener datos para el log
            $tasaActual = $this->obtenerTasa($id);
            if (!$tasaActual) {
                return [
                    'success' => false,
                    'message' => 'Tasa de crédito no encontrada'
                ];
            }
            
            // Eliminar definitivamente de la base de datos
            $sql = "DELETE FROM control_tasas_creditos WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(1, $id, PDO::PARAM_INT);
            
            $stmt->execute();
            
            $this->conn->commit();
            
            $this->logger->logEliminar('tasas_creditos', 'Tasa de crédito eliminada', $tasaActual);
            
            return [
                'success' => true,
                'message' => 'Tasa de crédito eliminada exitosamente'
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            return [
                'success' => false,
                'message' => 'Error al eliminar tasa de crédito: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Validar datos de tasa de crédito
     */
    public function validarDatos(array $data): array {
        $errores = [];
        
        if (empty($data['nombre_credito'])) {
            $errores[] = 'El nombre del crédito es requerido';
        } elseif (strlen($data['nombre_credito']) > 100) {
            $errores[] = 'El nombre del crédito no puede exceder 100 caracteres';
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
        
        if (empty($data['limite_meses']) || $data['limite_meses'] <= 0) {
            $errores[] = 'El límite de meses debe ser mayor a 0';
        }
        
        if (!isset($data['tasa']) || $data['tasa'] < 0) {
            $errores[] = 'La tasa debe ser mayor o igual a 0';
        }
        
        if (!isset($data['seguro_vida']) || $data['seguro_vida'] < 0) {
            $errores[] = 'El seguro de vida debe ser mayor o igual a 0';
        }
        
        if (!isset($data['seguro_deudores']) || $data['seguro_deudores'] < 0) {
            $errores[] = 'El seguro de deudores debe ser mayor o igual a 0';
        }
        
        return $errores;
    }
    
    /**
     * Obtener tasas activas para un rango de fechas
     */
    public function obtenerTasasActivas(string $fecha = null): array {
        $fecha = $fecha ?: date('Y-m-d');
        
        $sql = "SELECT * FROM control_tasas_creditos 
                WHERE estado_activo = TRUE 
                AND fecha_inicio <= ? 
                AND (fecha_fin IS NULL OR fecha_fin >= ?)
                ORDER BY fecha_inicio DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(1, $fecha, PDO::PARAM_STR);
        $stmt->bindValue(2, $fecha, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}
