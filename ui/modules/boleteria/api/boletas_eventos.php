<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../controllers/BoleteriaController.php';
require_once __DIR__ . '/../../../controllers/AuthController.php';

try {
    $auth = new AuthController();
    $auth->requireModule('boleteria.boletas');
    
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        throw new Exception('ID de boleta requerido');
    }
    
    $c = new BoleteriaController();
    $eventos = $c->boletas_obtener_eventos($id);
    
    echo json_encode(['success' => true, 'eventos' => $eventos]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
