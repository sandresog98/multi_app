<?php
require_once __DIR__ . '/../config/database.php';

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
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Encontrar la ruta base del proyecto
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $marker = '/ui/';
    $pos = strpos($scriptName, $marker);
    
    if ($pos !== false) {
        $basePath = substr($scriptName, 0, $pos);
    } else {
        // Fallback: usar la ruta base del proyecto
        $basePath = dirname($scriptName);
    }
    
    $baseUrl = $protocol . '://' . $host . $basePath;
    
    // Si la imagen ya tiene la ruta completa, usarla
    if (strpos($imagen, '/multi_app/ui/uploads/') === 0) {
        return $baseUrl . $imagen;
    } else {
        // La imagen tiene una ruta relativa, agregar base URL
        return $baseUrl . '/ui/' . ltrim($imagen, '/');
    }
}
?>
