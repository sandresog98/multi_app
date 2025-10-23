<?php
/**
 * API para obtener rutas dinámicas del sistema CX
 */
require_once __DIR__ . '/../config/paths.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    $rutas = [
        'baseUrl' => cx_getBaseUrl(),
        'fullBaseUrl' => cx_getFullBaseUrl(),
        'uiBaseUrl' => cx_getUiBaseUrl(),
        'cxBaseUrl' => cx_getCxBaseUrl(),
        'apiPublicidad' => cx_getUiBaseUrl() . '/api/cx_publicidad.php',
        'pages' => [
            'index' => cx_getBaseUrl() . 'index.php',
            'perfil' => cx_getBaseUrl() . 'modules/perfil/pages/index.php',
            'monetario' => cx_getBaseUrl() . 'modules/monetario/pages/index.php',
            'creditos' => cx_getBaseUrl() . 'modules/creditos/pages/index.php'
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $rutas
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener rutas: ' . $e->getMessage()
    ]);
}
?>
