<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../controllers/BoleteriaController.php';
require_once __DIR__ . '/../../../controllers/AuthController.php';

try {
    $auth = new AuthController();
    $auth->requireModule('boleteria.boletas');
    $c = new BoleteriaController();
    $categoria_id = $_GET['categoria_id'] ?? $_POST['categoria_id'] ?? null;
    $serial = $_GET['serial'] ?? $_POST['serial'] ?? '';
    if (!$categoria_id || $serial === '') { throw new Exception('Parámetros requeridos'); }
    $exists = $c->boletas_existe($categoria_id, $serial);
    echo json_encode(['success' => true, 'exists' => (bool)$exists]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}


