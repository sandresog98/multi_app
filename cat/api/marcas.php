<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../models/CatalogoProducto.php';

try {
    $catalogo = new CatalogoProducto();
    $marcas = $catalogo->obtenerMarcas();
    
    echo json_encode([
        'success' => true,
        'data' => $marcas
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
        'error' => $e->getMessage()
    ]);
}
