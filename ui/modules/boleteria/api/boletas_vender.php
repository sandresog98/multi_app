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
    $cedula = (string)($input['cedula'] ?? '');
    $metodo = (string)($input['metodo_venta'] ?? 'credito');
    if ($id <= 0) { throw new Exception('ID inválido'); }
    if ($cedula === '') { throw new Exception('Cédula requerida'); }
    $permitidos = ['credito', 'regalo_cooperativa'];
    if (!in_array($metodo, $permitidos, true)) { throw new Exception('Método de venta inválido'); }
    
    $result = $c->boletas_vender($id, $cedula, $metodo);
    
    // Log de venta de boleta
    if ($result['success']) {
        try {
            (new Logger())->logEditar('boleteria.boletas', 'Vender boleta', null, ['id' => $id, 'cedula' => $cedula, 'metodo' => $metodo]);
        } catch (Throwable $ignored) {}
    }
    
    echo json_encode($result);
} catch (Throwable $e) {
    try { (new Logger())->logEditar('boleteria.boletas', 'Error al vender boleta', null, ['error' => $e->getMessage()]); } catch (Throwable $ignored) {}
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}


