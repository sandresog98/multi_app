<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../models/Logger.php';
require_once '../models/Comunicacion.php';

header('Content-Type: application/json');

try {
	$auth = new AuthController();
	$auth->requireRole('admin');
	$user = $auth->getCurrentUser();

	if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Método no permitido');
	$cedula = trim($_POST['cedula'] ?? '');
	$tipo = trim($_POST['tipo'] ?? '');
	$estado = trim($_POST['estado'] ?? '');
	$comentario = trim($_POST['comentario'] ?? '');
	$fecha = trim($_POST['fecha'] ?? '');
	if (!$cedula || !$tipo || !$estado || !$fecha) throw new Exception('Datos incompletos');

	$model = new Comunicacion();
	$id = $model->crear($cedula, $tipo, $estado, $comentario, $fecha, (int)($user['id'] ?? null));

	// Log creación
	(new Logger())->logCrear('cobranza', 'Registrar comunicación', [
		'id' => $id,
		'cedula' => $cedula,
		'tipo' => $tipo,
		'estado' => $estado
	]);

	echo json_encode(['success'=>true,'id'=>$id]);
} catch (Throwable $e) {
	try { (new Logger())->logEditar('cobranza', 'Error al registrar comunicación', null, ['error'=>$e->getMessage()]); } catch (Throwable $ignored) {}
	http_response_code(400);
	echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}


