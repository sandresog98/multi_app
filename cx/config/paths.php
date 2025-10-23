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
    // Detectar si estamos en servidor de producción o desarrollo
    $isProduction = !empty($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== 'localhost' && strpos($_SERVER['HTTP_HOST'], '127.0.0.1') === false;
    
    if ($isProduction) {
        // En producción, construir URL completa
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $marker = '/cx/';
        $pos = strpos($script, $marker);
        
        if ($pos !== false) {
            $basePath = substr($script, 0, $pos);
        } else {
            // Buscar otros marcadores del proyecto
            $markers = ['/ui/', '/cat/', '/py/'];
            $basePath = '/multi_app'; // Valor por defecto
            
            foreach ($markers as $marker) {
                $pos = strpos($script, $marker);
                if ($pos !== false) {
                    $basePath = substr($script, 0, $pos);
                    break;
                }
            }
        }
        
        return $protocol . '://' . $host . $basePath;
    } else {
        // En desarrollo local, usar lógica original
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
    
    // Detectar el host de manera más robusta
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
    
    // Si estamos en CLI, usar localhost por defecto
    if (php_sapi_name() === 'cli') {
        $host = 'localhost';
    }
    
    // Log para debug (solo en desarrollo)
    if (defined('DEBUG') && DEBUG) {
        error_log("cx_getFullBaseUrl - HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'no definido'));
        error_log("cx_getFullBaseUrl - SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'no definido'));
        error_log("cx_getFullBaseUrl - Host detectado: " . $host);
    }
    
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
                $basePath = '/multi_app';
            }
        }
    }
    
    // Limpiar la ruta base para evitar puntos dobles
    $basePath = rtrim($basePath, '.');
    
    $fullUrl = $protocol . '://' . $host . $basePath;
    
    // Log para debug (solo en desarrollo)
    if (defined('DEBUG') && DEBUG) {
        error_log("cx_getFullBaseUrl - URL final: " . $fullUrl);
    }
    
    return $fullUrl;
}

// Obtener URL base para UI (para APIs)
function cx_getUiBaseUrl(): string {
    return cx_getFullBaseUrl() . '/ui';
}

// Obtener URL base para CX
function cx_getCxBaseUrl(): string {
    return cx_getFullBaseUrl() . '/cx';
}

// Función alternativa más robusta para detectar la URL base
function cx_getFullBaseUrlRobust(): string {
    // Detectar protocolo
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        $protocol = 'https';
    } elseif (isset($_SERVER['REQUEST_SCHEME'])) {
        $protocol = $_SERVER['REQUEST_SCHEME'];
    }
    
    // Detectar host de manera más robusta
    $host = 'localhost';
    if (isset($_SERVER['HTTP_HOST'])) {
        $host = $_SERVER['HTTP_HOST'];
    } elseif (isset($_SERVER['SERVER_NAME'])) {
        $host = $_SERVER['SERVER_NAME'];
        if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) {
            $host .= ':' . $_SERVER['SERVER_PORT'];
        }
    }
    
    // Detectar ruta base del proyecto
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $basePath = '/multi_app'; // Valor por defecto
    
    // Buscar marcadores del proyecto
    $markers = ['/cx/', '/ui/', '/cat/', '/py/'];
    foreach ($markers as $marker) {
        $pos = strpos($scriptName, $marker);
        if ($pos !== false) {
            $basePath = substr($scriptName, 0, $pos);
            break;
        }
    }
    
    // Si no encontramos marcadores, buscar multi_app en la ruta
    if ($basePath === '/multi_app') {
        $pathParts = explode('/', trim($scriptName, '/'));
        $multiAppPos = array_search('multi_app', $pathParts);
        if ($multiAppPos !== false) {
            $basePath = '/' . implode('/', array_slice($pathParts, 0, $multiAppPos + 1));
        }
    }
    
    return $protocol . '://' . $host . $basePath;
}
?>


