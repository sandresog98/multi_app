<?php
/**
 * Configuración de rutas para la interfaz CX (móvil)
 */

// Ruta base en filesystem (relative to this file)
define('CX_BASE_PATH', __DIR__ . '/..');

// URL base relativa al servidor para CX
function cx_getBaseUrl() {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $marker = '/cx/';
    $pos = strpos($scriptName, $marker);
    if ($pos !== false) {
        return substr($scriptName, 0, $pos + strlen($marker));
    }
    // Si no encontramos /cx/, usar la ruta base del proyecto
    $basePath = dirname($scriptName);
    return $basePath . '/cx/';
}

// URL base del repositorio (para acceder a ui, cx, etc.)
function cx_repo_base_url(): string {
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $marker = '/cx/';
    $pos = strpos($script, $marker);
    if ($pos !== false) {
        return substr($script, 0, $pos);
    }
    // Si no encontramos /cx/, usar la ruta base del proyecto
    $basePath = dirname($script);
    return $basePath;
}

// Ruta absoluta a partir de CX
function cx_path(string $relative): string {
    return CX_BASE_PATH . '/' . ltrim($relative, '/');
}

// Construir rutas de redirección en CX
function cx_redirect(string $path): string {
    // Usar una URL absoluta más simple
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $marker = '/cx/';
    $pos = strpos($scriptName, $marker);
    
    if ($pos !== false) {
        $baseUrl = substr($scriptName, 0, $pos + strlen($marker));
    } else {
        // Fallback: usar la ruta base del proyecto
        $basePath = dirname($scriptName);
        $baseUrl = $basePath . '/cx/';
    }
    
    $cleanPath = ltrim($path, '/');
    $fullUrl = $baseUrl . $cleanPath;
    
    return $fullUrl;
}
?>


