<?php
/**
 * Script de depuración para verificar rutas
 * Acceder desde: http://localhost/projects/multi_app/ui/modules/oficina/debug_paths.php
 */

require_once __DIR__ . '/../../config/paths.php';

echo "<h2>Información de Rutas</h2>";
echo "<pre>";

echo "=== INFORMACIÓN DEL SERVIDOR ===\n";
echo "SERVER NAME: " . ($_SERVER['SERVER_NAME'] ?? 'N/A') . "\n";
echo "HTTP HOST: " . ($_SERVER['HTTP_HOST'] ?? 'N/A') . "\n";
echo "SCRIPT NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "\n";
echo "REQUEST URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";
echo "DOCUMENT ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";

echo "\n=== BASE PATH ===\n";
echo "BASE_PATH definido: " . BASE_PATH . "\n";

echo "\n=== GET BASE URL ===\n";
echo "getBaseUrl(): " . getBaseUrl() . "\n";

echo "\n=== RUTAS DE ARCHIVOS ===\n";
$testDir = getAbsolutePath('uploads/recibos');
echo "Ruta absoluta uploads/recibos: " . $testDir . "\n";
echo "Directorio existe: " . (is_dir($testDir) ? "SÍ" : "NO") . "\n";
echo "Directorio es escribible: " . (is_writable($testDir) ? "SÍ" : "NO") . "\n";

echo "\n=== EJEMPLO DE URL GENERADA ===\n";
$ejemploUrl = getBaseUrl() . 'uploads/recibos/2025/10/ejemplo.jpg';
echo "URL de ejemplo: " . $ejemploUrl . "\n";

echo "\n=== ARCHIVOS EXISTENTES ===\n";
$year = date('Y');
$month = date('m');
$realdir = $testDir . '/' . $year . '/' . $month;
if (is_dir($realdir)) {
    echo "Directorio real: $realdir\n";
    $files = glob($realdir . '/*');
    echo "Archivos encontrados: " . count($files) . "\n";
    if (count($files) > 0) {
        echo "Primeros 3 archivos:\n";
        foreach (array_slice($files, 0, 3) as $file) {
            echo "  - " . basename($file) . "\n";
        }
    }
} else {
    echo "Directorio $realdir NO existe\n";
}

echo "\n=== URL WEB COMPLETA ===\n";
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrlFull = $protocol . '://' . $host . getBaseUrl();
echo "URL completa base: " . $baseUrlFull . "\n";
echo "URL de ejemplo completa: " . $baseUrlFull . "uploads/recibos/2025/10/ejemplo.jpg\n";

echo "</pre>";

