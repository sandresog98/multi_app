<?php
/**
 * Modelo para gestión de usuarios
 */

require_once __DIR__ . '/../config/database.php';

class User {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    /**
     * Verificar credenciales de usuario
     */
    public function verifyCredentials($username, $password) {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, usuario, password, nombre_completo, rol, email 
                FROM control_usuarios 
                WHERE usuario = ? AND estado_activo = TRUE
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                return $user;
            }
            return false;
        } catch (Exception $e) {
            error_log("Error verificando usuario: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener usuario por ID
     */
    public function getById($id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, usuario, nombre_completo, email, rol, estado_activo, fecha_creacion 
                FROM control_usuarios 
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error obteniendo usuario: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crear nuevo usuario
     */
    public function create($username, $password, $nombreCompleto, $email = null, $rol = 'usuario') {
        try {
            // Verificar si el usuario ya existe
            $checkStmt = $this->conn->prepare("SELECT id FROM control_usuarios WHERE usuario = ?");
            $checkStmt->execute([$username]);
            
            if ($checkStmt->fetch()) {
                return ['success' => false, 'message' => 'El usuario ya existe'];
            }
            
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $this->conn->prepare("
                INSERT INTO control_usuarios (usuario, password, nombre_completo, email, rol) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([$username, $hashedPassword, $nombreCompleto, $email, $rol]);
            
            return ['success' => true, 'message' => 'Usuario creado exitosamente'];
        } catch (Exception $e) {
            error_log("Error creando usuario: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al crear usuario: ' . $e->getMessage()];
        }
    }
    
    /**
     * Obtener todos los usuarios
     */
    public function getAll() {
        try {
            $stmt = $this->conn->query("
                SELECT id, usuario, nombre_completo, email, rol, estado_activo as estado, fecha_creacion 
                FROM control_usuarios 
                ORDER BY nombre_completo
            ");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error obteniendo usuarios: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Actualizar usuario
     */
    public function update($id, $nombreCompleto, $email = null, $rol = 'usuario', $estado = 1) {
        try {
            $sql = "UPDATE control_usuarios SET 
                    nombre_completo = ?, 
                    email = ?, 
                    rol = ?, 
                    estado_activo = ? 
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$nombreCompleto, $email, $rol, $estado, $id]);
            
            return ['success' => true, 'message' => 'Usuario actualizado exitosamente'];
        } catch (Exception $e) {
            error_log("Error actualizando usuario: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al actualizar usuario: ' . $e->getMessage()];
        }
    }
    
    /**
     * Cambiar contraseña
     */
    public function changePassword($id, $newPassword) {
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("
                UPDATE control_usuarios 
                SET password = ? 
                WHERE id = ?
            ");
            $stmt->execute([$hashedPassword, $id]);
            
            return ['success' => true, 'message' => 'Contraseña actualizada exitosamente'];
        } catch (Exception $e) {
            error_log("Error cambiando contraseña: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al cambiar contraseña: ' . $e->getMessage()];
        }
    }
    
    /**
     * Eliminar usuario
     */
    public function delete($id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM control_usuarios WHERE id = ?");
            $stmt->execute([$id]);
            
            return ['success' => true, 'message' => 'Usuario eliminado exitosamente'];
        } catch (Exception $e) {
            error_log("Error eliminando usuario: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al eliminar usuario: ' . $e->getMessage()];
        }
    }
    
    /**
     * Obtener conexión (para scripts de prueba)
     */
    public function getConnection() {
        return $this->conn;
    }
}
?> 