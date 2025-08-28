<?php
/**
 * Configuración de la base de datos
 */

// Detección de entorno: APP_ENV (development|production). Fallback: auto local -> development
$__appEnv = getenv('APP_ENV');
if ($__appEnv === false || $__appEnv === '') {
    $isCli = php_sapi_name() === 'cli';
    $serverName = $_SERVER['SERVER_NAME'] ?? '';
    $serverAddr = $_SERVER['SERVER_ADDR'] ?? '';
    $httpHost   = $_SERVER['HTTP_HOST'] ?? '';
    $looksLocal = (
        $serverName === 'localhost' ||
        $httpHost === 'localhost' ||
        $serverAddr === '127.0.0.1' ||
        strpos($httpHost, '.local') !== false ||
        $isCli // en CLI, por defecto asumimos desarrollo salvo que se defina APP_ENV
    );
    $__appEnv = $looksLocal ? 'development' : 'production';
}
define('APP_ENV', $__appEnv);

// Configuración basada en entorno, con posibilidad de override por variables de entorno DB_*
if (APP_ENV === 'development') {
    $dbHost = getenv('DB_HOST');
    $dbUser = getenv('DB_USER');
    $dbPass = getenv('DB_PASS');
    $dbName = getenv('DB_NAME');
    $dbHost = ($dbHost === false || $dbHost === '') ? 'localhost' : $dbHost;
    $dbUser = ($dbUser === false || $dbUser === '') ? 'root' : $dbUser;
    $dbPass = ($dbPass === false) ? '' : $dbPass; // contraseña vacía por defecto
    $dbName = ($dbName === false || $dbName === '') ? 'multiapptwo' : $dbName;
} else { // production
    $dbHost = getenv('DB_HOST');
    $dbUser = getenv('DB_USER');
    $dbPass = getenv('DB_PASS');
    $dbName = getenv('DB_NAME');
    $dbHost = ($dbHost === false || $dbHost === '') ? '192.168.10.30' : $dbHost;
    $dbUser = ($dbUser === false || $dbUser === '') ? 'root' : $dbUser;
    $dbPass = ($dbPass === false || $dbPass === '') ? '123456789' : $dbPass;
    $dbName = ($dbName === false || $dbName === '') ? 'multiapptwo' : $dbName;
}

// Definiciones para compatibilidad
define('DB_HOST', $dbHost);
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);
define('DB_NAME', $dbName);

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Prevenir clonación
    private function __clone() {}
    
    // Prevenir deserialización
    public function __wakeup() {}
}

// Función para obtener conexión (compatibilidad)
function getConnection() {
    return Database::getInstance()->getConnection();
}

// Función para verificar si la conexión está activa
function testConnection() {
    try {
        $conn = Database::getInstance()->getConnection();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?> 