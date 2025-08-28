<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../controllers/BoleteriaController.php';
require_once __DIR__ . '/../../../controllers/AuthController.php';

try {
    $auth = new AuthController();
    $auth->requireModule('boleteria.boletas');
    $c = new BoleteriaController();
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) { $input = $_POST; }
    $id = (int)($input['id'] ?? 0);
    $cedula = (string)($input['cedula'] ?? '');
    $metodo = (string)($input['metodo_venta'] ?? '');
    $comprobante = isset($input['comprobante']) ? (string)$input['comprobante'] : null;
    if ($id <= 0) { throw new Exception('ID inválido'); }
    if ($cedula === '') { throw new Exception('Cédula requerida'); }
    $permitidos = ['Directa','Incentivos','Credito'];
    if ($metodo === '' || !in_array($metodo, $permitidos, true)) { throw new Exception('Método de venta inválido'); }
    echo json_encode($c->boletas_vender($id, $cedula, $metodo, $comprobante));
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}


