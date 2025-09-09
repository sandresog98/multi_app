<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../models/Logger.php';
require_once '../models/Comunicacion.php';

header('Content-Type: application/json');

try {
	$auth = new AuthController();
	$auth->requireModule('cobranza.comunicaciones');
	$user = $auth->getCurrentUser();

	if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Método no permitido');
	$id = (int)($_POST['id'] ?? 0);
	$tipo = trim($_POST['tipo'] ?? '');
	$estado = trim($_POST['estado'] ?? '');
	$comentario = trim($_POST['comentario'] ?? '');
	$fecha = trim($_POST['fecha'] ?? '');
	if ($estado === 'Sin comunicación') { $estado = 'Sin respuesta'; }
	$allowedEstados = ['Informa de pago realizado', 'Comprometido a realizar el pago', 'Sin respuesta'];
	if (!$id || !$tipo || !$estado || !$fecha) throw new Exception('Datos incompletos');
	if (!in_array($estado, $allowedEstados, true)) throw new Exception('Estado inválido');

	$model = new Comunicacion();
	$updated = $model->actualizar($id, (int)$user['id'], $tipo, $estado, $comentario, $fecha);
	if ($updated === 0) throw new Exception('No autorizado o sin cambios');

	(new Logger())->logEditar('cobranza', 'Editar comunicación', null, [ 'id' => $id, 'tipo' => $tipo, 'estado' => $estado ]);
	echo json_encode(['success'=>true]);
} catch (Throwable $e) {
	try { (new Logger())->logEditar('cobranza', 'Error al editar comunicación', null, ['error'=>$e->getMessage()]); } catch (Throwable $ignored) {}
	http_response_code(400);
	echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
}


