<?php
/**
 * Script temporal para verificar URLs generadas
 */
require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/PagoCashQr.php';

header('Content-Type: application/json');

try {
    $model = new PagoCashQr();
    
    // Obtener algunos registros con links
    $conn = getConnection();
    $stmt = $conn->query("
        SELECT confiar_id, link_validacion, estado, fecha_validacion
        FROM banco_confirmacion_confiar 
        WHERE link_validacion IS NOT NULL 
        ORDER BY fecha_validacion DESC 
        LIMIT 5
    ");
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $resultados = [];
    foreach ($registros as $reg) {
        $url = $reg['link_validacion'];
        $resultados[] = [
            'confiar_id' => $reg['confiar_id'],
            'url_guardada' => $url,
            'url_base' => getBaseUrl(),
            'archivo_existe' => false
        ];
        
        // Intentar verificar si el archivo existe
        if (strpos($url, '/multi_app/') === 0) {
            $relativePath = str_replace('/multi_app/ui/', '', $url);
            $fullPath = __DIR__ . '/../../' . $relativePath;
            if (file_exists($fullPath)) {
                $resultados[count($resultados) - 1]['archivo_existe'] = true;
                $resultados[count($resultados) - 1]['ruta_fisica'] = $fullPath;
                $resultados[count($resultados) - 1]['tamaño'] = filesize($fullPath) . ' bytes';
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'getBaseUrl' => getBaseUrl(),
        'registros' => $resultados,
        'dir_uploads_recibos' => __DIR__ . '/../../uploads/recibos',
        'dir_existe' => is_dir(__DIR__ . '/../../uploads/recibos'),
        'directorios_disponibles' => []
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

