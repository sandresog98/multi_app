<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../controllers/BoleteriaController.php';
require_once __DIR__ . '/../../../controllers/AuthController.php';

try {
    $auth = new AuthController();
    $auth->requireModule('boleteria.boletas');
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) { $input = $_POST; }
    
    $id = $input['id'] ?? null;
    $comprobante = $input['comprobante'] ?? null;
    
    if (!$id) {
        throw new Exception('ID de boleta requerido');
    }
    
    if (!$comprobante) {
        throw new Exception('Comprobante requerido');
    }
    
    $c = new BoleteriaController();
    $res = $c->boletas_contabilizar($id, $comprobante);
    
    echo json_encode($res);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
