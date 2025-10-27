<?php
/**
 * Servidor de archivos para recibos
 * Protege el acceso directo a los archivos de recibos
 */
// Obtener ruta del archivo
$file = $_GET['f'] ?? '';

if (empty($file)) {
    http_response_code(404);
    die('Archivo no especificado');
}

// Construir ruta completa desde la raíz de ui/
$fullPath = __DIR__ . '/' . $file;

// Verificar que el archivo existe
if (!file_exists($fullPath)) {
    http_response_code(404);
    die('Archivo no encontrado');
}

// Verificar que es un archivo (no directorio)
if (!is_file($fullPath)) {
    http_response_code(403);
    die('Acceso denegado');
}

// Verificar extensión permitida
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
if (!in_array($ext, $allowedExt)) {
    http_response_code(403);
    die('Tipo de archivo no permitido');
}

// Determinar content type
$contentTypes = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
    'pdf' => 'application/pdf'
];
$contentType = $contentTypes[$ext] ?? 'application/octet-stream';

// Limpiar cualquier output previo
if (ob_get_level()) {
    ob_end_clean();
}

// Enviar headers
header('Content-Type: ' . $contentType);
header('Content-Length: ' . filesize($fullPath));
header('Cache-Control: public, max-age=31536000');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($fullPath)) . ' GMT');

// Enviar archivo
readfile($fullPath);
exit;

