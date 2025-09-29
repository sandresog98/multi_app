<?php
/**
 * Logger para CX: registra actividades de asociados
 */

require_once __DIR__ . '/../config/database.php';

class Logger {
    private $conn;

    public function __construct() {
        $this->conn = getConnection();
    }

    private function getClientIP() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    private function log($accion, $modulo, $detalle = '', $nivel = 'info', $datos_anteriores = null, $datos_nuevos = null, $cedula = null) {
        try {
            if (session_status() === PHP_SESSION_NONE) { session_start(); }
            $id_usuario = $cedula ?? $_SESSION['cedula'] ?? null;
            $ip_address = $this->getClientIP();
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

            $sql = "INSERT INTO control_logs (id_usuario, accion, modulo, detalle, ip_address, user_agent, datos_anteriores, datos_nuevos, nivel)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $id_usuario,
                $accion,
                $modulo,
                $detalle,
                $ip_address,
                $user_agent,
                $datos_anteriores ? json_encode($datos_anteriores, JSON_UNESCAPED_UNICODE) : null,
                $datos_nuevos ? json_encode($datos_nuevos, JSON_UNESCAPED_UNICODE) : null,
                $nivel
            ]);
            return true;
        } catch (Exception $e) {
            error_log('CX Logger error: ' . $e->getMessage());
            return false;
        }
    }

    public function logLogin($cedula, $exitoso = true) {
        $detalle = $exitoso ? "Login exitoso: $cedula" : "Login fallido: $cedula";
        $nivel = $exitoso ? 'info' : 'warning';
        return $this->log('login', 'cx.auth', $detalle, $nivel, null, null, $cedula);
    }

    public function logPasswordRequest($cedula) {
        $detalle = "Solicitud de código de recuperación: $cedula";
        return $this->log('crear', 'cx.password', $detalle, 'info', null, ['cedula' => $cedula], $cedula);
    }

    public function logPasswordReset($cedula, $exitoso = true) {
        $detalle = $exitoso ? "Contraseña restablecida exitosamente: $cedula" : "Error al restablecer contraseña: $cedula";
        $nivel = $exitoso ? 'info' : 'warning';
        return $this->log('editar', 'cx.password', $detalle, $nivel, null, ['cedula' => $cedula], $cedula);
    }

    public function logPasswordCreate($cedula, $exitoso = true) {
        $detalle = $exitoso ? "Contraseña creada exitosamente: $cedula" : "Error al crear contraseña: $cedula";
        $nivel = $exitoso ? 'info' : 'warning';
        return $this->log('crear', 'cx.password', $detalle, $nivel, null, ['cedula' => $cedula], $cedula);
    }

    public function logLogout($cedula) {
        $detalle = "Logout: $cedula";
        return $this->log('login', 'cx.auth', $detalle, 'info', null, null, $cedula);
    }

    // Lectura de logs para CX
    public function getLogs($filtros = []) {
        $where = [];
        $params = [];

        if (!empty($filtros['cedula'])) { $where[] = "l.id_usuario = ?"; $params[] = $filtros['cedula']; }
        if (!empty($filtros['modulo'])) { $where[] = "l.modulo = ?"; $params[] = $filtros['modulo']; }
        if (!empty($filtros['accion'])) { $where[] = "l.accion = ?"; $params[] = $filtros['accion']; }
        if (!empty($filtros['nivel'])) { $where[] = "l.nivel = ?"; $params[] = $filtros['nivel']; }
        if (!empty($filtros['fecha_desde'])) { $where[] = "DATE(l.timestamp) >= ?"; $params[] = $filtros['fecha_desde']; }
        if (!empty($filtros['fecha_hasta'])) { $where[] = "DATE(l.timestamp) <= ?"; $params[] = $filtros['fecha_hasta']; }

        $whereClause = !empty($where) ? ("WHERE " . implode(" AND ", $where)) : "";
        $limit = (int)($filtros['limite'] ?? 50);
        $page = max(1, (int)($filtros['page'] ?? 1));
        $offset = ($page - 1) * $limit;

        $sql = "SELECT l.*, s.nombre as nombre_asociado
                FROM control_logs l 
                LEFT JOIN sifone_asociados s ON l.id_usuario = s.cedula 
                $whereClause 
                ORDER BY l.timestamp DESC 
                LIMIT $limit OFFSET $offset";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Total para paginación
        $countSql = "SELECT COUNT(*) c FROM control_logs l $whereClause";
        $cstmt = $this->conn->prepare($countSql);
        $cstmt->execute($params);
        $total = (int)($cstmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);

        return [ 'items' => $rows, 'total' => $total, 'pages' => $limit>0 ? (int)ceil($total/$limit) : 1, 'current_page' => $page ];
    }

    public function getEstadisticas($fecha_desde = null, $fecha_hasta = null) {
        $where = [];
        $params = [];
        if ($fecha_desde) { $where[] = "DATE(timestamp) >= ?"; $params[] = $fecha_desde; }
        if ($fecha_hasta) { $where[] = "DATE(timestamp) <= ?"; $params[] = $fecha_hasta; }
        $whereClause = !empty($where) ? ("WHERE " . implode(" AND ", $where)) : "";
        $sql = "SELECT 
                    COUNT(*) as total_logs,
                    COUNT(DISTINCT id_usuario) as asociados_activos,
                    COUNT(CASE WHEN accion = 'crear' THEN 1 END) as creaciones,
                    COUNT(CASE WHEN accion = 'editar' THEN 1 END) as ediciones,
                    COUNT(CASE WHEN accion = 'eliminar' THEN 1 END) as eliminaciones,
                    COUNT(CASE WHEN nivel = 'error' THEN 1 END) as errores
                FROM control_logs 
                $whereClause";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getLogById($id) {
        $sql = "SELECT l.*, s.nombre as nombre_asociado
                FROM control_logs l 
                LEFT JOIN sifone_asociados s ON l.id_usuario = s.cedula 
                WHERE l.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

?>
