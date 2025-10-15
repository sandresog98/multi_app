<?php
/**
 * Configuración de rutas para el catálogo público
 */

function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = $_SERVER['SCRIPT_NAME'];
    $path = dirname($script);
    
    // Ajustar para la estructura del proyecto
    $basePath = str_replace('/cat', '', $path);
    return $protocol . '://' . $host . $basePath . '/cat/';
}

function getAssetUrl($asset) {
    return getBaseUrl() . 'assets/' . $asset;
}
