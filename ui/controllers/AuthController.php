<?php
/**
 * Controlador para autenticación
 */

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../models/Logger.php';

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
}
?> 