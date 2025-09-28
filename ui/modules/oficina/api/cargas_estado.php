<?php
require_once '../../../controllers/AuthController.php';
require_once '../models/Cargas.php';

header('Content-Type: application/json');

try {
    $auth = new AuthController();
    $auth->requireModule('oficina.cargas');

    $m = new Cargas();
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 20;
    $offset = ($page - 1) * $limit;

    $items = $m->listar($limit, $offset);
    $total = $m->contar();
    $totalPages = (int)ceil($total / $limit);

    echo json_encode([
        'success' => true,
        'items' => $items,
        'pagination' => [
            'page' => $page,
            'pageSize' => $limit,
            'total' => $total,
            'totalPages' => $totalPages
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}

