<?php
require_once '../../../controllers/AuthController.php';
require_once '../models/Cobranza.php';

header('Content-Type: application/json');

try {
	$auth = new AuthController();
	$auth->requireModule('cobranza.comunicaciones');
	$cedula = $_GET['cedula'] ?? '';
	if (!$cedula) { throw new Exception('Cédula requerida'); }
	$model = new Cobranza();
	$detalleCompleto = $model->obtenerDetalleCompletoAsociado($cedula);
	echo json_encode(['success'=>true,'data'=>$detalleCompleto]);
} catch (Throwable $e) {
	http_response_code(400);
	echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}


