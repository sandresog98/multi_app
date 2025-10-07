<?php
require_once __DIR__ . '/../../../config/database.php';

class Comunicacion {
	private $conn;

	public function __construct() {
		$this->conn = getConnection();
	}

    public function crear($asociadoCedula, $tipo, $estado, $comentario, $fechaComunicacion, $idUsuario, $tipoOrigen = 'credito') {
        $sql = "INSERT INTO cobranza_comunicaciones 
            (asociado_cedula, tipo_comunicacion, estado, comentario, fecha_comunicacion, id_usuario, tipo_origen)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$asociadoCedula, $tipo, $estado, $comentario, $fechaComunicacion, $idUsuario, $tipoOrigen]);
		return (int)$this->conn->lastInsertId();
	}

    public function listarPorCedula($asociadoCedula, $limit = 50, $tipoOrigen = null) {
        $extra = '';
        $params = [$asociadoCedula];
        if ($tipoOrigen !== null && $tipoOrigen !== '') { $extra = ' AND c.tipo_origen = ?'; $params[] = $tipoOrigen; }
        $sql = "SELECT c.*, u.nombre_completo AS usuario_nombre 
            FROM cobranza_comunicaciones c 
            LEFT JOIN control_usuarios u ON u.id = c.id_usuario
            WHERE c.asociado_cedula = ?" . $extra . "
            ORDER BY c.fecha_comunicacion DESC, c.fecha_creacion DESC
            LIMIT $limit";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
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


