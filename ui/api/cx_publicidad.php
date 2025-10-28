<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../../cx/config/paths.php';

// API pública para obtener publicidad activa (sin autenticación)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    $conn = getConnection();
    
    $tipo = $_GET['tipo'] ?? 'pagina_principal';
    
    // Validar tipo
    $tiposValidos = ['pagina_principal', 'perfil', 'creditos', 'monetario'];
    if (!in_array($tipo, $tiposValidos)) {
        echo json_encode(['success' => false, 'message' => 'Tipo de publicidad no válido']);
        exit;
    }
    
    $sql = "SELECT id, tipo, nombre, descripcion, imagen, fecha_inicio, fecha_fin 
            FROM control_cx_publicidad 
            WHERE tipo = ? 
            AND fecha_inicio <= CURDATE() 
            AND fecha_fin >= CURDATE() 
            ORDER BY fecha_creacion DESC 
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$tipo]);
    $publicidad = $stmt->fetch();
    
    if ($publicidad) {
        // Construir URL completa de la imagen usando rutas dinámicas
        $publicidad['imagen_url'] = getImageUrl($publicidad['imagen']);
        
        echo json_encode([
            'success' => true, 
            'data' => $publicidad
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'No hay publicidad activa'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}

function getImageUrl($imagen) {
    // Si la imagen ya es una URL completa (http/https), devolverla tal como está
    if (strpos($imagen, 'http://') === 0 || strpos($imagen, 'https://') === 0) {
        return $imagen;
    }
    
    // Obtener la URL base usando la función de ui/config/paths.php que es más confiable
    $baseUrl = getBaseUrl(); // Esta es la función de ui/config/paths.php
    
    // Extraer la ruta relativa del archivo
    $relativePath = '';
    
    // Si la imagen ya contiene serve_file.php, extraer el parámetro f=
    if (strpos($imagen, 'serve_file.php?f=') !== false) {
        $parts = explode('serve_file.php?f=', $imagen);
        if (isset($parts[1])) {
            $relativePath = $parts[1];
        }
    } 
    // Si la imagen tiene /multi_app/ui/uploads/cx_publicidad/
    elseif (strpos($imagen, '/multi_app/ui/uploads/') !== false) {
        $relativePath = str_replace('/multi_app/ui/uploads/', 'uploads/', $imagen);
    }
    // Si la imagen tiene /projects/multi_app/ui/uploads/
    elseif (strpos($imagen, '/projects/multi_app/ui/uploads/') !== false) {
        $relativePath = str_replace('/projects/multi_app/ui/uploads/', 'uploads/', $imagen);
    }
    // Si es solo el nombre del archivo o ruta simple
    else {
        $cleanImage = ltrim($imagen, '/');
        if (strpos($cleanImage, 'cx_publicidad/') === false) {
            $cleanImage = 'uploads/cx_publicidad/' . $cleanImage;
        } else {
            $cleanImage = 'uploads/' . $cleanImage;
        }
        $relativePath = $cleanImage;
    }
    
    // Construir URL completa con serve_file.php
    $finalUrl = $baseUrl . 'serve_file.php?f=' . $relativePath;
    
    error_log("cx_publicidad getImageUrl - Input: $imagen, baseUrl: $baseUrl, relativePath: $relativePath, finalUrl: $finalUrl");
    
    return $finalUrl;
}
?>
