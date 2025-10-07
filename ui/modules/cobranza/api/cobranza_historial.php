<?php
require_once '../../../controllers/AuthController.php';
require_once '../models/Comunicacion.php';

header('Content-Type: application/json');

try {
	$auth = new AuthController();
	$auth->requireModule('cobranza.comunicaciones');
	$cedula = $_GET['cedula'] ?? '';
	if (!$cedula) { throw new Exception('Cédula requerida'); }
    $tipoOrigen = $_GET['tipo_origen'] ?? null;
    $model = new Comunicacion();
    $items = $model->listarPorCedula($cedula, 200, $tipoOrigen);
	echo json_encode(['success'=>true,'items'=>$items]);
} catch (Throwable $e) {
	http_response_code(400);
	echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}


