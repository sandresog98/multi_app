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
    // Si la imagen ya es una URL completa (http/https), devolverla tal como está
    if (strpos($imagen, 'http://') === 0 || strpos($imagen, 'https://') === 0) {
        return $imagen;
    }
    
    // Si ya es una URL con serve_file.php, devolverla tal como está
    if (strpos($imagen, 'serve_file.php') !== false) {
        // Obtener la URL base completa usando el sistema de rutas dinámicas
        $baseUrl = cx_getFullBaseUrlRobust();
        // Si ya tiene /multi_app/ui/ al inicio, simplemente concatenar
        if (strpos($imagen, '/multi_app/ui/') === 0) {
            return str_replace('/multi_app', $baseUrl, $imagen);
        }
        return $baseUrl . '/ui/' . $imagen;
    }
    
    // Obtener la URL base completa usando el sistema de rutas dinámicas
    $baseUrl = cx_getFullBaseUrlRobust();
    
    // Si la imagen empieza con /multi_app/ui/, convertir a usar serve_file.php
    if (strpos($imagen, '/multi_app/ui/uploads/cx_publicidad/') !== false) {
        // Extraer la ruta relativa después de uploads/
        $relativePath = str_replace('/multi_app/ui/uploads/', 'uploads/', $imagen);
        return $baseUrl . '/multi_app/ui/serve_file.php?f=' . $relativePath;
    }
    
    // Si la imagen tiene una ruta antigua sin serve_file.php, convertirla
    if (strpos($imagen, '/multi_app/ui/uploads/') !== false) {
        $relativePath = str_replace('/multi_app/ui/uploads/', 'uploads/', $imagen);
        return $baseUrl . '/multi_app/ui/serve_file.php?f=' . $relativePath;
    }
    
    // Si es una ruta relativa con serve_file ya construido
    if (strpos($imagen, 'serve_file.php?f=') !== false) {
        return $baseUrl . '/multi_app/ui/' . $imagen;
    }
    
    // Fallback: construir URL manualmente
    $cleanImage = ltrim($imagen, '/');
    if (strpos($cleanImage, 'cx_publicidad/') === false) {
        $cleanImage = 'cx_publicidad/' . $cleanImage;
    }
    return $baseUrl . '/multi_app/ui/serve_file.php?f=uploads/' . $cleanImage;
}
?>
