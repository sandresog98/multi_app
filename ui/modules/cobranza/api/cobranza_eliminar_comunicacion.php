<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../models/Logger.php';
require_once '../models/Comunicacion.php';

header('Content-Type: application/json');

try {
	$auth = new AuthController();
	$auth->requireAnyRole(['admin', 'oficina']);
	$user = $auth->getCurrentUser();

	if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Método no permitido');
	$id = (int)($_POST['id'] ?? 0);
	if (!$id) throw new Exception('ID requerido');

	$model = new Comunicacion();
	$deleted = $model->eliminar($id, (int)$user['id']);
	if ($deleted === 0) throw new Exception('No autorizado');

	(new Logger())->logEliminar('cobranza', 'Eliminar comunicación', [ 'id' => $id ]);
	echo json_encode(['success'=>true]);
} catch (Throwable $e) {
	try { (new Logger())->logEditar('cobranza', 'Error al eliminar comunicación', null, ['error'=>$e->getMessage()]); } catch (Throwable $ignored) {}
	http_response_code(400);
	echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}


