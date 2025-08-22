<?php
/**
 * Configuración de la base de datos
 */

// Configuración de la base de datos
define('DB_HOST', '192.168.10.30');
define('DB_USER', 'root');
define('DB_PASS', '123456789');
define('DB_NAME', 'multiapptwo');

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