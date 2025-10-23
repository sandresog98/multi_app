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

// Obtener URL base completa del servidor (protocolo + host + puerto)
function cx_getFullBaseUrl(): string {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    
    // Encontrar la ruta base del proyecto
    $marker = '/cx/';
    $pos = strpos($scriptName, $marker);
    
    if ($pos !== false) {
        $basePath = substr($scriptName, 0, $pos);
    } else {
        // Buscar otros marcadores del proyecto
        $markers = ['/ui/', '/cat/', '/py/'];
        $basePath = null;
        
        foreach ($markers as $marker) {
            $pos = strpos($scriptName, $marker);
            if ($pos !== false) {
                $basePath = substr($scriptName, 0, $pos);
                break;
            }
        }
        
        // Si no encontramos ningún marcador, usar dirname pero limitar la profundidad
        if ($basePath === null) {
            $basePath = dirname($scriptName);
            
            // Si la ruta es muy profunda (más de 2 niveles), buscar el directorio multi_app
            $pathParts = explode('/', trim($basePath, '/'));
            if (count($pathParts) > 2) {
                $multiAppPos = array_search('multi_app', $pathParts);
                if ($multiAppPos !== false) {
                    $basePath = '/' . implode('/', array_slice($pathParts, 0, $multiAppPos + 1));
                }
            }
            
            // Si estamos en CLI o la ruta está vacía, usar una ruta por defecto
            if (empty($basePath) || $basePath === '.') {
                $basePath = '/projects/multi_app';
            }
        }
    }
    
    // Limpiar la ruta base para evitar puntos dobles
    $basePath = rtrim($basePath, '.');
    
    return $protocol . '://' . $host . $basePath;
}

// Obtener URL base para UI (para APIs)
function cx_getUiBaseUrl(): string {
    return cx_getFullBaseUrl() . '/ui';
}

// Obtener URL base para CX
function cx_getCxBaseUrl(): string {
    return cx_getFullBaseUrl() . '/cx';
}
?>


