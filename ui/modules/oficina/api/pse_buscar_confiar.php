<?php
require_once '../../../controllers/AuthController.php';
require_once '../models/PagoPse.php';

header('Content-Type: application/json');

try {
    $auth = new AuthController();
    $auth->requireModule('oficina.transacciones');

    $pseId = $_GET['pse_id'] ?? '';
    $q = trim($_GET['q'] ?? '');
    $fecha = trim($_GET['fecha'] ?? '');

    if (!$pseId) { echo json_encode(['success'=>false,'message'=>'pse_id requerido']); exit; }

    $model = new PagoPse();
    $items = $model->getConfiarMatchesForPse($pseId, $q, 0, $fecha ?: null);
    echo json_encode(['success'=>true,'items'=>$items]);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
    exit;
}
