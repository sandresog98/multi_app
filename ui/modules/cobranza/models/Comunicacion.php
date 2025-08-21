<?php
require_once __DIR__ . '/../../../config/database.php';

class Comunicacion {
	private $conn;

	public function __construct() {
		$this->conn = getConnection();
	}

	public function crear($asociadoCedula, $tipo, $estado, $comentario, $fechaComunicacion, $idUsuario) {
		$sql = "INSERT INTO cobranza_comunicaciones 
			(asociado_cedula, tipo_comunicacion, estado, comentario, fecha_comunicacion, id_usuario)
			VALUES (?, ?, ?, ?, ?, ?)";
		$stmt = $this->conn->prepare($sql);
		$stmt->execute([$asociadoCedula, $tipo, $estado, $comentario, $fechaComunicacion, $idUsuario]);
		return (int)$this->conn->lastInsertId();
	}

	public function listarPorCedula($asociadoCedula, $limit = 50) {
		$sql = "SELECT c.*, u.nombre_completo AS usuario_nombre 
			FROM cobranza_comunicaciones c 
			LEFT JOIN control_usuarios u ON u.id = c.id_usuario
			WHERE c.asociado_cedula = ?
			ORDER BY c.fecha_comunicacion DESC, c.fecha_creacion DESC
			LIMIT $limit";
		$stmt = $this->conn->prepare($sql);
		$stmt->execute([$asociadoCedula]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function actualizar($id, $idUsuario, $tipo, $estado, $comentario, $fechaComunicacion) {
		$sql = "UPDATE cobranza_comunicaciones 
				SET tipo_comunicacion = ?, estado = ?, comentario = ?, fecha_comunicacion = ?
				WHERE id = ? AND id_usuario = ?";
		$stmt = $this->conn->prepare($sql);
		$stmt->execute([$tipo, $estado, $comentario, $fechaComunicacion, $id, $idUsuario]);
		return $stmt->rowCount();
	}

    public function eliminar($id, $idUsuario) {
        $sql = "DELETE FROM cobranza_comunicaciones WHERE id = ? AND id_usuario = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id, $idUsuario]);
        return $stmt->rowCount();
    }
}
?>


