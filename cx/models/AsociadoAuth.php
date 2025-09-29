<?php
require_once __DIR__ . '/../config/database.php';

class AsociadoAuth {
    private $conn;

    public function __construct() {
        $this->conn = cx_getConnection();
    }

    public static function normalizeDocumento(string $doc): string {
        $onlyDigits = preg_replace('/\D+/', '', $doc);
        // Eliminar ceros a la izquierda
        $trimmed = ltrim($onlyDigits, '0');
        return $trimmed !== '' ? $trimmed : '0';
    }

    public function findAsociadoByDocumento(string $doc): ?array {
        $norm = self::normalizeDocumento($doc);
        // Igualar por valor numérico de documento (CAST AS UNSIGNED)
        $sql = "SELECT a.cedula, a.nombre, a.mail, a.celula
                FROM sifone_asociados a
                WHERE CAST(a.cedula AS UNSIGNED) = CAST(? AS UNSIGNED)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$norm]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function verifyPassword(string $doc, string $password): ?array {
        $assoc = $this->findAsociadoByDocumento($doc);
        if (!$assoc) { 
            error_log("DEBUG: Asociado no encontrado para documento: $doc");
            return null; 
        }
        $cedulaReal = $assoc['cedula'];
        $stmt = $this->conn->prepare("SELECT password_hash FROM control_asociados WHERE cedula = ?");
        $stmt->execute([$cedulaReal]);
        $row = $stmt->fetch();
        if (!$row || empty($row['password_hash'])) { 
            error_log("DEBUG: No hay password_hash para cedula: $cedulaReal");
            return null; 
        }
        
        error_log("DEBUG: Verificando password para cedula: $cedulaReal");
        error_log("DEBUG: Password ingresado: " . substr($password, 0, 3) . "...");
        error_log("DEBUG: Hash almacenado: " . substr($row['password_hash'], 0, 20) . "...");
        
        $ok = password_verify($password, $row['password_hash']);
        error_log("DEBUG: Verificación password: " . ($ok ? "ÉXITO" : "FALLO"));
        
        return $ok ? $assoc : null;
    }

    public function setPassword(string $doc, string $newPassword): bool {
        $assoc = $this->findAsociadoByDocumento($doc);
        if (!$assoc) { return false; }
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $sql = "INSERT INTO control_asociados (cedula, password_hash, password_set_at, estado_activo, fecha_actualizacion)
                VALUES (?, ?, CURRENT_TIMESTAMP, TRUE, CURRENT_TIMESTAMP)
                ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), password_set_at = CURRENT_TIMESTAMP, estado_activo = TRUE, fecha_actualizacion = CURRENT_TIMESTAMP";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$assoc['cedula'], $hash]);
    }

    public function hasPassword(string $doc): bool {
        $assoc = $this->findAsociadoByDocumento($doc);
        if (!$assoc) { return false; }
        $stmt = $this->conn->prepare("SELECT password_hash FROM control_asociados WHERE cedula = ?");
        $stmt->execute([$assoc['cedula']]);
        $row = $stmt->fetch();
        return !empty($row) && !empty($row['password_hash']);
    }

    public function createResetToken(string $doc): ?array {
        $assoc = $this->findAsociadoByDocumento($doc);
        if (!$assoc) { return null; }
        $token = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT); // código de 6 dígitos
        $expires = (new DateTime('+20 minutes'))->format('Y-m-d H:i:s');
        $sql = "INSERT INTO control_asociados (cedula, reset_token, reset_token_expires_at, fecha_actualizacion)
                VALUES (?, ?, ?, CURRENT_TIMESTAMP)
                ON DUPLICATE KEY UPDATE reset_token = VALUES(reset_token), reset_token_expires_at = VALUES(reset_token_expires_at), fecha_actualizacion = CURRENT_TIMESTAMP";
        $stmt = $this->conn->prepare($sql);
        $ok = $stmt->execute([$assoc['cedula'], $token, $expires]);
        if (!$ok) { return null; }
        return [
            'cedula' => $assoc['cedula'],
            'nombre' => $assoc['nombre'] ?? '',
            'email' => $assoc['mail'] ?? '',
            'token' => $token,
            'expires_at' => $expires,
        ];
    }

    public function verifyResetTokenAndSetPassword(string $doc, string $token, string $newPassword): bool {
        $assoc = $this->findAsociadoByDocumento($doc);
        if (!$assoc) { return false; }
        $stmt = $this->conn->prepare("SELECT reset_token, reset_token_expires_at FROM control_asociados WHERE cedula = ?");
        $stmt->execute([$assoc['cedula']]);
        $row = $stmt->fetch();
        if (!$row || empty($row['reset_token'])) { return false; }
        $now = new DateTime();
        $exp = !empty($row['reset_token_expires_at']) ? new DateTime($row['reset_token_expires_at']) : null;
        if (!$exp || $now > $exp) { return false; }
        if (trim((string)$row['reset_token']) !== trim((string)$token)) { return false; }

        // Token válido: actualizar contraseña y limpiar token
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        
        error_log("DEBUG: Estableciendo nueva contraseña para cedula: " . $assoc['cedula']);
        error_log("DEBUG: Nueva contraseña: " . substr($newPassword, 0, 3) . "...");
        error_log("DEBUG: Hash generado: " . substr($hash, 0, 20) . "...");
        
        $up = $this->conn->prepare("UPDATE control_asociados
                                    SET password_hash = ?, password_set_at = CURRENT_TIMESTAMP,
                                        reset_token = NULL, reset_token_expires_at = NULL,
                                        estado_activo = TRUE, fecha_actualizacion = CURRENT_TIMESTAMP
                                    WHERE cedula = ?");
        $result = $up->execute([$hash, $assoc['cedula']]);
        
        error_log("DEBUG: Actualización de contraseña: " . ($result ? "ÉXITO" : "FALLO"));
        
        return $result;
    }
}
?>


