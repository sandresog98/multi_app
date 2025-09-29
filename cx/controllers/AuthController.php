<?php
require_once __DIR__ . '/../models/AsociadoAuth.php';
require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../models/Logger.php';

class CxAuthController {
    private $model;
    private $logger;

    public function __construct() {
        $this->model = new AsociadoAuth();
        $this->logger = new Logger();
        $this->ensureSession();
    }

    private function ensureSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('multiapptwo_cx');
            session_start();
        }
        
        // Debug temporal
        if (isset($_GET['debug'])) {
            error_log("Session status: " . session_status());
            error_log("Session ID: " . session_id());
            error_log("Session data: " . print_r($_SESSION, true));
        }
    }

    public function login(string $documento, string $password): array {
        if (trim($documento) === '' || trim($password) === '') {
            return ['success'=>false,'message'=>'Documento y contraseña son requeridos'];
        }
        // Si no tiene contraseña aún, redirigir a crear/recuperar
        if (!$this->model->hasPassword($documento)) {
            return ['success'=>false,'redirect'=>'password_request.php', 'message'=>'Aún no has creado tu contraseña'];
        }
        $assoc = $this->model->verifyPassword($documento, $password);
        if (!$assoc) { 
            // Log de login fallido
            $this->logger->logLogin($documento, false);
            return ['success'=>false,'message'=>'Documento o contraseña inválidos']; 
        }
        
        // Limpiar sesión anterior y establecer nueva
        $_SESSION = [];
        $_SESSION['cx_cedula'] = $assoc['cedula'];
        $_SESSION['cx_nombre'] = $assoc['nombre'] ?? '';
        $_SESSION['cx_email'] = $assoc['mail'] ?? '';
        $_SESSION['cx_login_at'] = time();
        $_SESSION['cx_last_activity'] = time();
        
        // Log de login exitoso
        $this->logger->logLogin($assoc['cedula'], true);
        
        // Forzar escritura de sesión
        session_write_close();
        session_start();
        
        return ['success'=>true,'redirect'=>'pages/index.php'];
    }

    public function requestReset(string $documento): array {
        $data = $this->model->createResetToken($documento);
        if (!$data) { return ['success'=>false,'message'=>'Documento no encontrado']; }
        $email = trim((string)($data['email'] ?? ''));
        if ($email === '') { return ['success'=>false,'message'=>'No hay email registrado']; }

        // Enviar email usando helper propio
        require_once __DIR__ . '/../utils/email_helper.php';
        require_once __DIR__ . '/../utils/email_templates.php';
        $body = cx_build_reset_email_html($data['nombre'] ?? '', $data['token']);
        $ok = sendEmail($email, 'Código para crear/restablecer contraseña', $body, true);
        if (!$ok) { 
            return ['success'=>false,'message'=>'No fue posible enviar el correo']; 
        }
        
        // Log de solicitud de código
        $this->logger->logPasswordRequest($documento);
        
        return ['success'=>true,'message'=>'Código enviado a su correo'];
    }

    public function confirmReset(string $documento, string $token, string $newPassword): array {
        if (strlen($newPassword) < 6) {
            return ['success'=>false,'message'=>'La contraseña debe tener al menos 6 caracteres'];
        }
        $ok = $this->model->verifyResetTokenAndSetPassword($documento, $token, $newPassword);
        if (!$ok) { 
            // Log de error al restablecer contraseña
            $this->logger->logPasswordReset($documento, false);
            return ['success'=>false,'message'=>'Código inválido o vencido']; 
        }
        
        // Log de restablecimiento exitoso
        $this->logger->logPasswordReset($documento, true);
        
        return ['success'=>true,'message'=>'Contraseña actualizada, puede iniciar sesión'];
    }

    public function requireAuth() {
        $this->ensureSession();
        if (empty($_SESSION['cx_cedula'])) {
            $loginUrl = $this->getLoginUrl();
            header('Location: ' . $loginUrl);
            exit;
        }
        $now = time();
        $last = (int)($_SESSION['cx_last_activity'] ?? $now);
        if (($now - $last) > 3600) { // 1h
            $this->logout();
            $loginUrl = $this->getLoginUrl() . '?timeout=1';
            header('Location: ' . $loginUrl);
            exit;
        }
        $_SESSION['cx_last_activity'] = $now;
    }

    private function getLoginUrl(): string {
        $currentScript = $_SERVER['SCRIPT_NAME'] ?? '';
        if (strpos($currentScript, '/pages/') !== false) {
            return '../login.php';
        } elseif (strpos($currentScript, '/modules/') !== false) {
            return '../../../login.php';
        }
        return 'login.php';
    }

    public function logout(): void {
        $this->ensureSession();
        
        // Log de logout antes de limpiar la sesión
        if (!empty($_SESSION['cx_cedula'])) {
            $this->logger->logLogout($_SESSION['cx_cedula']);
        }
        
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}
?>


