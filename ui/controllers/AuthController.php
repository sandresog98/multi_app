<?php
/**
 * Controlador para autenticación
 */

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../models/Logger.php';
require_once __DIR__ . '/../config/database.php';

class AuthController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    /**
     * Asegurar sesión con nombre aislado para v2
     */
    private function ensureSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // Usar un nombre de sesión distinto para v2 para no heredar sesiones de v1
            session_name('multiapptwo_session');
            session_start();
        }
    }
    
    /**
     * Procesar login
     */
    public function login($username, $password) {
        // Validar datos de entrada
        if (empty($username) || empty($password)) {
            return [
                'success' => false,
                'message' => 'Por favor, complete todos los campos.'
            ];
        }
        
        // Limpiar datos de entrada
        $username = $this->cleanInput($username);
        
        // Verificar credenciales
        $user = $this->userModel->verifyCredentials($username, $password);
        
        if ($user) {
            // Iniciar sesión
            $this->ensureSession();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['usuario'];
            $_SESSION['nombre_completo'] = $user['nombre_completo'];
            $_SESSION['user_role'] = $user['rol'];
            $_SESSION['user_email'] = $user['email'];
            // Marcas de tiempo para control de sesión por inactividad
            $_SESSION['login_at'] = time();
            $_SESSION['last_activity'] = time();
            // Log login exitoso
            (new Logger())->logLogin($username, true);
            
            return [
                'success' => true,
                'message' => 'Login exitoso',
                'redirect' => getRedirectPath('pages/dashboard.php')
            ];
        } else {
            // Log login fallido
            (new Logger())->logLogin($username, false);
            return [
                'success' => false,
                'message' => 'Usuario o contraseña incorrectos.'
            ];
        }
    }
    
    /**
     * Cerrar sesión
     */
    public function logout() {
        $this->ensureSession();
        
        // Destruir todas las variables de sesión
        $_SESSION = array();
        
        // Si se desea destruir la sesión completamente, borrar también la cookie de sesión
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Finalmente, destruir la sesión
        session_destroy();
        
        return [
            'success' => true,
            'message' => 'Sesión cerrada exitosamente',
            'redirect' => getRedirectPath('login.php')
        ];
    }
    
    /**
     * Verificar si el usuario está autenticado
     */
    public function isAuthenticated() {
        $this->ensureSession();
        
        // Verificar que existan todos los datos necesarios de la sesión
        return isset($_SESSION['user_id']) && 
               !empty($_SESSION['user_id']) && 
               isset($_SESSION['username']) && 
               isset($_SESSION['nombre_completo']) && 
               isset($_SESSION['user_role']);
    }

    /**
     * Enforce idle session timeout. If exceeded, logout and redirect to login with timeout flag.
     */
    private function enforceIdleTimeout(int $idleSeconds = 3600): void {
        $this->ensureSession();
        if (!isset($_SESSION['user_id'])) { return; }
        $now = time();
        $last = (int)($_SESSION['last_activity'] ?? $now);
        if (($now - $last) > $idleSeconds) {
            $this->logout();
            header("Location: " . getRedirectPath('login.php') . "?timeout=1");
            exit();
        }
        $_SESSION['last_activity'] = $now;
    }
    
    /**
     * Verificar rol del usuario
     */
    public function hasRole($role) {
        $this->ensureSession();
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
    }
    
    /**
     * Obtener datos del usuario actual
     */
    public function getCurrentUser() {
        $this->ensureSession();
        if ($this->isAuthenticated()) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'nombre_completo' => $_SESSION['nombre_completo'],
                'rol' => $_SESSION['user_role'],
                'email' => $_SESSION['user_email'] ?? ''
            ];
        }
        return null;
    }
    
    /**
     * Limpiar datos de entrada
     */
    private function cleanInput($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
    
    /**
     * Requerir autenticación
     */
    public function requireAuth() {
        // Enforce 1-hour idle timeout on every protected request
        $this->enforceIdleTimeout(3600);
        if (!$this->isAuthenticated()) {
            header("Location: " . getRedirectPath('login.php'));
            exit();
        }
    }
    
    /**
     * Requerir rol específico
     */
    public function requireRole($role) {
        $this->requireAuth();
        if (!$this->hasRole($role)) {
            header("Location: " . getRedirectPath('modules/oficina/pages/index.php'));
            exit();
        }
    }

    /**
     * Requerir cualquiera de los roles indicados. El rol 'admin' siempre tiene acceso.
     */
    public function requireAnyRole(array $roles) {
        $this->requireAuth();
        $this->ensureSession();
        $current = $_SESSION['user_role'] ?? '';
        if ($current === 'admin') { return; }
        foreach ($roles as $r) {
            if ($current === $r) { return; }
        }
        header("Location: " . getRedirectPath('modules/oficina/pages/index.php'));
        exit();
    }

    /**
     * Cargar permisos por rol desde roles.json en la sesión.
     */
    private function ensurePermissionsLoaded() {
        $this->ensureSession();
        $currentRole = $_SESSION["user_role"] ?? '';
        $rolesPath = __DIR__ . '/../../roles.json';
        $currentRolesMtime = is_file($rolesPath) ? @filemtime($rolesPath) : 0;

        $needsReload = false;
        if (!isset($_SESSION['module_perms_loaded']) || !$_SESSION['module_perms_loaded']) {
            $needsReload = true;
        }
        if (($_SESSION['perm_role'] ?? null) !== $currentRole) {
            $needsReload = true;
        }
        if (($currentRolesMtime !== 0) && (($_SESSION['roles_json_mtime'] ?? 0) !== $currentRolesMtime)) {
            $needsReload = true;
        }

        if (!$needsReload) { return; }

        $_SESSION['module_permissions'] = [];
        $_SESSION['perm_role'] = $currentRole;
        $_SESSION['roles_json_mtime'] = $currentRolesMtime;

        if (!$currentRole) { $_SESSION['module_perms_loaded'] = true; return; }
        if ($currentRole === 'admin') { // admin: acceso total
            $_SESSION['module_permissions'] = ['*'];
            $_SESSION['module_perms_loaded'] = true;
            return;
        }

        try {
            if (!is_file($rolesPath)) {
                $_SESSION['module_permissions'] = [];
            } else {
                $json = json_decode(file_get_contents($rolesPath), true);
                $mods = $json['roles'][$currentRole]['modulos'] ?? [];
                if (!is_array($mods)) { $mods = []; }
                // Normalizar a strings únicas
                $mods = array_values(array_unique(array_map('strval', $mods)));
                $_SESSION['module_permissions'] = $mods;
            }
        } catch (Throwable $e) {
            $_SESSION['module_permissions'] = [];
        }
        $_SESSION['module_perms_loaded'] = true;
    }

    /**
     * Verificar permiso de acceso a un módulo (clave como 'boleteria.boletas').
     */
    public function canAccessModule(string $moduleKey): bool {
        $this->requireAuth();
        $this->ensurePermissionsLoaded();
        $role = $_SESSION['user_role'] ?? '';
        if ($role === 'admin') return true;
        $perms = $_SESSION['module_permissions'] ?? [];
        if (in_array('*', $perms, true)) return true;
        // Coincidencia exacta o por prefijo (e.g., 'boleteria' permite 'boleteria.boletas')
        foreach ($perms as $perm) {
            if ($perm === $moduleKey) return true;
            if ($perm !== '' && (strpos($moduleKey, $perm . '.') === 0)) return true;
        }
        return false;
    }

    /**
     * Requerir permiso de acceso a un módulo.
     */
    public function requireModule(string $moduleKey) {
        if ($this->canAccessModule($moduleKey)) { return; }
        // Si es una llamada a API, devolver JSON 403; si no, redirigir
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $isApi = (strpos($script, '/api/') !== false) || (stripos($accept, 'application/json') !== false);
        if ($isApi) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
            exit();
        }
        header("Location: " . getRedirectPath('modules/oficina/pages/index.php'));
        exit();
    }
}
?> 