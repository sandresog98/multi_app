<?php
require_once '../../../controllers/AuthController.php';
require_once '../models/Cargas.php';

header('Content-Type: application/json');

try {
    $auth = new AuthController();
    $auth->requireModule('oficina.cargas');

    $m = new Cargas();
    $items = $m->listar(100);
    echo json_encode(['success'=>true,'items'=>$items]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}

