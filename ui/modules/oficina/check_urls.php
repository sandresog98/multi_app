<?php
/**
 * Script temporal para verificar URLs generadas
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Verificación de URLs</h2><pre>";

try {
    require_once __DIR__ . '/../../config/paths.php';
    echo "✓ paths.php cargado\n";
    
    require_once __DIR__ . '/../../config/database.php';
    echo "✓ database.php cargado\n";
    
    echo "\n=== BASE INFO ===\n";
    echo "getBaseUrl(): " . getBaseUrl() . "\n";
    echo "BASE_PATH: " . (defined('BASE_PATH') ? BASE_PATH : 'NO DEFINIDO') . "\n";
    
    echo "\n=== DATABASE ===\n";
    $conn = getConnection();
    $stmt = $conn->query("
        SELECT confiar_id, link_validacion, estado, fecha_validacion
        FROM banco_confirmacion_confiar 
        WHERE link_validacion IS NOT NULL 
        ORDER BY fecha_validacion DESC 
        LIMIT 5
    ");
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Registros encontrados: " . count($registros) . "\n\n";
    
    foreach ($registros as $reg) {
        $url = $reg['link_validacion'];
        echo "\nConfiar ID: " . $reg['confiar_id'] . "\n";
        echo "URL guardada: $url\n";
        
        // Intentar verificar si el archivo existe
        if (strpos($url, '/multi_app/') === 0) {
            $relativePath = str_replace('/multi_app/ui/', '', $url);
            $fullPath = __DIR__ . '/../../' . $relativePath;
            echo "Ruta relativa: $relativePath\n";
            echo "Ruta completa: $fullPath\n";
            echo "Archivo existe: " . (file_exists($fullPath) ? "SÍ" : "NO") . "\n";
            if (file_exists($fullPath)) {
                echo "Tamaño: " . filesize($fullPath) . " bytes\n";
            }
        }
        echo "---\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
} catch (Throwable $e) {
    echo "ERROR FATAL: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "</pre>";


