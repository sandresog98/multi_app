<?php
require_once __DIR__ . '/../config/database.php';
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
    // Si la imagen ya es una URL completa, devolverla tal como está
    if (strpos($imagen, 'http://') === 0 || strpos($imagen, 'https://') === 0) {
        return $imagen;
    }
    
    // Obtener la URL base completa usando el sistema de rutas dinámicas
    $baseUrl = cx_getFullBaseUrl();
    
    // Si la imagen empieza con /multi_app/, usar directamente la ruta completa
    if (strpos($imagen, '/multi_app/') === 0) {
        // Reemplazar /multi_app/ con la URL base completa
        return str_replace('/multi_app', $baseUrl, $imagen);
    }
    
    // Si la imagen empieza con /projects/multi_app/, usar directamente la ruta completa
    if (strpos($imagen, '/projects/multi_app/') === 0) {
        // Reemplazar /projects/multi_app/ con la URL base completa
        return str_replace('/projects/multi_app', $baseUrl, $imagen);
    }
    
    // Si es una ruta relativa, construir la URL completa
    $cleanImage = ltrim($imagen, '/');
    
    // Verificar si la imagen ya incluye el directorio cx_publicidad
    if (strpos($cleanImage, 'cx_publicidad/') === 0) {
        return $baseUrl . '/ui/uploads/' . $cleanImage;
    } else {
        // Si no incluye el directorio, agregarlo
        return $baseUrl . '/ui/uploads/cx_publicidad/' . $cleanImage;
    }
}
?>
