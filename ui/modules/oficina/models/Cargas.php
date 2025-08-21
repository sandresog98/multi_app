<?php
require_once __DIR__ . '/../../../config/database.php';

class Cargas {
    private $conn;
    public function __construct() { $this->conn = getConnection(); }

    public function crearJob(string $tipo, string $archivoRuta, ?int $usuarioId): int {
        $sql = "INSERT INTO control_cargas (tipo, archivo_ruta, usuario_id) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$tipo, $archivoRuta, $usuarioId]);
        return (int)$this->conn->lastInsertId();
    }

    public function listar(int $limit = 50): array {
        $stmt = $this->conn->prepare("SELECT id, tipo, archivo_ruta, estado, mensaje_log, fecha_creacion, fecha_actualizacion FROM control_cargas ORDER BY id DESC LIMIT ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}


