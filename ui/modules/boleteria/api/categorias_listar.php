<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../controllers/BoleteriaController.php';
require_once __DIR__ . '/../../../controllers/AuthController.php';

try {
    $auth = new AuthController();
    $auth->requireModule('boleteria.categorias');
    $c = new BoleteriaController();
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 20);
    $search = trim($_GET['search'] ?? '');
    $estado = trim($_GET['estado'] ?? '');
    $sortBy = trim($_GET['sort_by'] ?? 'nombre');
    $sortDir = trim($_GET['sort_dir'] ?? 'ASC');
    echo json_encode(['success' => true, 'data' => $c->categorias_listar($page, $limit, $search, $estado, $sortBy, $sortDir)]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}


