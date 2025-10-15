<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../models/CatalogoProducto.php';

try {
    $catalogo = new CatalogoProducto();
    
    // Obtener parámetros de la URL
    $filtros = [];
    $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    $porPagina = isset($_GET['por_pagina']) ? (int)$_GET['por_pagina'] : 12;
    $ordenar = isset($_GET['ordenar']) ? $_GET['ordenar'] : 'nombre';
    $direccion = isset($_GET['direccion']) ? strtoupper($_GET['direccion']) : 'ASC';
    
    // Aplicar filtros
    if (!empty($_GET['categoria_id'])) {
        $filtros['categoria_id'] = (int)$_GET['categoria_id'];
    }
    
    if (!empty($_GET['marca_id'])) {
        $filtros['marca_id'] = (int)$_GET['marca_id'];
    }
    
    if (!empty($_GET['nombre'])) {
        $filtros['nombre'] = trim($_GET['nombre']);
    }
    
    if (!empty($_GET['precio_min'])) {
        $filtros['precio_min'] = (float)$_GET['precio_min'];
    }
    
    if (!empty($_GET['precio_max'])) {
        $filtros['precio_max'] = (float)$_GET['precio_max'];
    }
    
    // Validar parámetros
    $pagina = max(1, $pagina);
    $porPagina = max(1, min(50, $porPagina)); // Máximo 50 productos por página
    
    $resultado = $catalogo->obtenerProductos($filtros, $pagina, $porPagina, $ordenar, $direccion);
    
    echo json_encode([
        'success' => true,
        'data' => $resultado
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
        'error' => $e->getMessage()
    ]);
}
