<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../controllers/BoleteriaController.php';
require_once __DIR__ . '/../../../controllers/AuthController.php';

try {
    $auth = new AuthController();
    $auth->requireModule('boleteria.boletas');
    $c = new BoleteriaController();
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 20);
    $filters = [
        'categoria_id' => $_GET['categoria_id'] ?? null,
        'estado' => $_GET['estado'] ?? null,
        'serial' => $_GET['serial'] ?? '',
        'cedula' => $_GET['cedula'] ?? '',
        'fecha_creacion_desde' => $_GET['fc_desde'] ?? '',
        'fecha_creacion_hasta' => $_GET['fc_hasta'] ?? '',
        'fecha_vendida_desde' => $_GET['fv_desde'] ?? '',
        'fecha_vendida_hasta' => $_GET['fv_hasta'] ?? '',
        'fecha_vencimiento_desde' => $_GET['fven_desde'] ?? '',
        'fecha_vencimiento_hasta' => $_GET['fven_hasta'] ?? ''
    ];
    $sortBy = $_GET['sort_by'] ?? 'id';
    $sortDir = $_GET['sort_dir'] ?? 'DESC';
    echo json_encode(['success' => true, 'data' => $c->boletas_listar($page, $limit, $filters, $sortBy, $sortDir)]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}


