<?php
/**
 * Configuración de base de datos para el catálogo público
 */

function getConnection() {
    $host = 'localhost';
    $dbname = 'multiapptwo';
    $username = 'root';
    $password = '123456789';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Error de conexión a la base de datos: " . $e->getMessage());
        throw new Exception("Error de conexión a la base de datos");
    }
}
