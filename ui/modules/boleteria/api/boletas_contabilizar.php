<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../controllers/BoleteriaController.php';
require_once __DIR__ . '/../../../controllers/AuthController.php';
require_once __DIR__ . '/../../../models/Logger.php';

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
    
    // Log de contabilización de boleta
    if ($res['success']) {
        try {
            (new Logger())->logEditar('boleteria.boletas', 'Contabilizar boleta', null, ['id' => $id, 'comprobante' => $comprobante]);
        } catch (Throwable $ignored) {}
    }
    
    echo json_encode($res);
} catch (Throwable $e) {
    try { (new Logger())->logEditar('boleteria.boletas', 'Error al contabilizar boleta', null, ['error' => $e->getMessage()]); } catch (Throwable $ignored) {}
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
