<?php
require_once __DIR__ . '/../../../config/database.php';

class CxPublicidad {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    public function crearPublicidad(array $datos): array {
        try {
            // Validar datos requeridos
            if (empty($datos['tipo']) || empty($datos['nombre']) || empty($datos['fecha_inicio']) || empty($datos['fecha_fin'])) {
                return ['success' => false, 'message' => 'Faltan datos requeridos'];
            }
            
            // Validar fechas
            $fechaInicio = new DateTime($datos['fecha_inicio']);
            $fechaFin = new DateTime($datos['fecha_fin']);
            
            if ($fechaFin <= $fechaInicio) {
                return ['success' => false, 'message' => 'La fecha de fin debe ser posterior a la fecha de inicio'];
            }
            
            // Validar que la fecha de inicio no sea anterior a hoy
            $hoy = new DateTime();
            $hoy->setTime(0, 0, 0);
            if ($fechaInicio < $hoy) {
                return ['success' => false, 'message' => 'La fecha de inicio no puede ser anterior a hoy'];
            }
            
            $sql = "INSERT INTO control_cx_publicidad (tipo, nombre, descripcion, imagen, fecha_inicio, fecha_fin, creado_por) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                $datos['tipo'],
                $datos['nombre'],
                $datos['descripcion'] ?? null,
                $datos['imagen'],
                $datos['fecha_inicio'],
                $datos['fecha_fin'],
                $datos['creado_por']
            ]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Publicidad creada exitosamente', 'id' => $this->conn->lastInsertId()];
            } else {
                $errorInfo = $stmt->errorInfo();
                return ['success' => false, 'message' => 'Error al crear la publicidad: ' . $errorInfo[2]];
            }
            
        } catch (Exception $e) {
            error_log('Error en crearPublicidad: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    public function obtenerPublicidades(): array {
        $sql = "SELECT p.*, u.nombre_completo as creado_por_nombre 
                FROM control_cx_publicidad p 
                LEFT JOIN control_usuarios u ON p.creado_por = u.id 
                ORDER BY p.fecha_creacion DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function obtenerPublicidadActiva(string $tipo = 'pagina_principal'): ?array {
        $sql = "SELECT * FROM control_cx_publicidad 
                WHERE tipo = ? 
                AND fecha_inicio <= CURDATE() 
                AND fecha_fin >= CURDATE() 
                ORDER BY fecha_creacion DESC 
                LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$tipo]);
        return $stmt->fetch() ?: null;
    }
    
    public function eliminarPublicidad(int $id): array {
        try {
            $sql = "DELETE FROM control_cx_publicidad WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([$id]);
            
            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Publicidad eliminada exitosamente'];
            } else {
                return ['success' => false, 'message' => 'No se encontró la publicidad o ya fue eliminada'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    public function obtenerPublicidadPorId(int $id): ?array {
        $sql = "SELECT * FROM control_cx_publicidad WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
    
    public function actualizarPublicidad(int $id, array $datos): array {
        try {
            // Validar fechas si se proporcionan
            if (isset($datos['fecha_inicio']) && isset($datos['fecha_fin'])) {
                $fechaInicio = new DateTime($datos['fecha_inicio']);
                $fechaFin = new DateTime($datos['fecha_fin']);
                
                if ($fechaFin <= $fechaInicio) {
                    return ['success' => false, 'message' => 'La fecha de fin debe ser posterior a la fecha de inicio'];
                }
            }
            
            $campos = [];
            $valores = [];
            
            foreach ($datos as $campo => $valor) {
                if ($campo !== 'id') {
                    $campos[] = "$campo = ?";
                    $valores[] = $valor;
                }
            }
            
            if (empty($campos)) {
                return ['success' => false, 'message' => 'No hay datos para actualizar'];
            }
            
            $valores[] = $id;
            $sql = "UPDATE control_cx_publicidad SET " . implode(', ', $campos) . " WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute($valores);
            
            if ($result) {
                return ['success' => true, 'message' => 'Publicidad actualizada exitosamente'];
            } else {
                return ['success' => false, 'message' => 'Error al actualizar la publicidad'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}
?>
