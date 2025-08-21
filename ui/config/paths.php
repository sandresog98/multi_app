<?php
/**
 * Configuración de rutas para la aplicación
 */

// Definir la ruta base de la aplicación
define('BASE_PATH', __DIR__ . '/..');

// Función para obtener la URL base absoluta de la UI (server-relative)
function getBaseUrl() {
    $scriptName = $_SERVER['SCRIPT_NAME']; // p.ej. /process/multi_app/v2/ui/modules/oficina/pages/index.php
    $marker = '/ui/';
    $pos = strpos($scriptName, $marker);
    if ($pos !== false) {
        // Devuelve /process/multi_app/v2/ui/
        return substr($scriptName, 0, $pos + strlen($marker));
    }
    // Fallback relativo
    return './';
}

// Función para obtener rutas absolutas en el filesystem
function getAbsolutePath($relativePath) {
    return BASE_PATH . '/' . $relativePath;
}

// Función para obtener rutas de redirección
function getRedirectPath($path) {
    return getBaseUrl() . $path;
}
?> 