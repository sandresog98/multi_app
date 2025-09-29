<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../controllers/BoleteriaController.php';
require_once __DIR__ . '/../../../controllers/AuthController.php';
require_once __DIR__ . '/../../../models/Logger.php';

try {
    $auth = new AuthController();
    $auth->requireModule('boleteria.boletas');
    $c = new BoleteriaController();
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) { $input = $_POST; }
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) { throw new Exception('ID inválido'); }
    
    $result = $c->boletas_deshacer_venta($id);
    
    // Log de deshacer venta de boleta
    if ($result['success']) {
        try {
            (new Logger())->logEditar('boleteria.boletas', 'Deshacer venta de boleta', null, ['id' => $id]);
        } catch (Throwable $ignored) {}
    }
    
    echo json_encode($result);
} catch (Throwable $e) {
    try { (new Logger())->logEditar('boleteria.boletas', 'Error al deshacer venta de boleta', null, ['error' => $e->getMessage()]); } catch (Throwable $ignored) {}
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}


