<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../models/CxPublicidad.php';
require_once __DIR__ . '/../../../controllers/AuthController.php';
require_once __DIR__ . '/../../../utils/FileUploadManager.php';
require_once __DIR__ . '/../../../../cx/config/paths.php';

try {
    $auth = new AuthController();
    $auth->requireModule('cx_control.publicidad');
    $currentUser = $auth->getCurrentUser();
    
    $publicidadModel = new CxPublicidad();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'crear') {
            // Validar datos requeridos
            if (empty($_POST['tipo']) || empty($_POST['nombre']) || empty($_POST['fecha_inicio']) || empty($_POST['fecha_fin'])) {
                throw new Exception('Faltan datos requeridos');
            }
            
            // Manejar archivo de imagen
            $imagenRuta = '/multi_app/ui/assets/img/logo.png'; // Por defecto
            
            if (!empty($_FILES['imagen']) && is_uploaded_file($_FILES['imagen']['tmp_name'])) {
                try {
                    $options = [
                        'maxSize' => 2 * 1024 * 1024, // 2MB
                        'allowedExtensions' => ['jpg', 'jpeg', 'png'],
                        'prefix' => 'publicidad_' . time(),
                        'userId' => $currentUser['id'],
                        'webPath' => '/multi_app/ui/uploads/cx_publicidad'
                    ];
                    
                    $baseDir = __DIR__ . '/../../../uploads/cx_publicidad';
                    $result = FileUploadManager::saveUploadedFile($_FILES['imagen'], $baseDir, $options);
                    $imagenRuta = $result['webUrl'];
                    
                } catch (Exception $e) {
                    throw new Exception('Error guardando imagen: ' . $e->getMessage());
                }
            }
            
            $datos = [
                'tipo' => $_POST['tipo'],
                'nombre' => $_POST['nombre'],
                'descripcion' => $_POST['descripcion'] ?? '',
                'imagen' => $imagenRuta,
                'fecha_inicio' => $_POST['fecha_inicio'],
                'fecha_fin' => $_POST['fecha_fin'],
                'creado_por' => $currentUser['id']
            ];
            
            $resultado = $publicidadModel->crearPublicidad($datos);
            echo json_encode($resultado);
            
        } elseif ($action === 'eliminar') {
            $id = (int)($_POST['id'] ?? 0);
            $resultado = $publicidadModel->eliminarPublicidad($id);
            echo json_encode($resultado);
        } else {
            throw new Exception('Acción no válida: ' . $action);
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';
        
        if ($action === 'listar') {
            $publicidades = $publicidadModel->obtenerPublicidades();
            
            // Agregar información de estado y URLs de imagen
            foreach ($publicidades as &$pub) {
                $hoy = new DateTime();
                $fechaInicio = new DateTime($pub['fecha_inicio']);
                $fechaFin = new DateTime($pub['fecha_fin']);
                
                if ($hoy < $fechaInicio) {
                    $pub['estado'] = 'Programada';
                    $pub['estado_class'] = 'text-warning';
                } elseif ($hoy > $fechaFin) {
                    $pub['estado'] = 'Expirada';
                    $pub['estado_class'] = 'text-muted';
                } else {
                    $pub['estado'] = 'Activa';
                    $pub['estado_class'] = 'text-success';
                }
                
                // Formatear fechas
                $pub['fecha_inicio_formatted'] = $fechaInicio->format('d/m/Y');
                $pub['fecha_fin_formatted'] = $fechaFin->format('d/m/Y');
                
                // Generar URL completa de la imagen
                $pub['imagen_url'] = getImageUrl($pub['imagen']);
            }
            
            echo json_encode(['success' => true, 'data' => $publicidades]);
        } else {
            throw new Exception('Acción no válida: ' . $action);
        }
    } else {
        throw new Exception('Método no permitido: ' . $_SERVER['REQUEST_METHOD']);
    }
    
} catch (Throwable $e) {
    error_log('Error en API publicidad: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function getImageUrl($imagen) {
    // Si la imagen ya es una URL completa, devolverla tal como está
    if (strpos($imagen, 'http://') === 0 || strpos($imagen, 'https://') === 0) {
        return $imagen;
    }
    
    // Detectar si estamos en servidor de producción o desarrollo
    $isProduction = !empty($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== 'localhost' && strpos($_SERVER['HTTP_HOST'], '127.0.0.1') === false;
    
    if ($isProduction) {
        // En producción, usar la URL base del servidor
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        
        // Detectar la ruta base del proyecto en el servidor
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = '/multi_app'; // Valor por defecto para producción
        
        // Buscar marcadores del proyecto en la ruta
        $markers = ['/cx/', '/ui/', '/cat/', '/py/'];
        foreach ($markers as $marker) {
            $pos = strpos($scriptName, $marker);
            if ($pos !== false) {
                $basePath = substr($scriptName, 0, $pos);
                break;
            }
        }
        
        $baseUrl = $protocol . '://' . $host . $basePath;
    } else {
        // En desarrollo local, usar la función existente
        $baseUrl = cx_getFullBaseUrlRobust();
    }
    
    // Si la imagen empieza con /multi_app/, usar directamente la ruta completa
    if (strpos($imagen, '/multi_app/') === 0) {
        // Reemplazar /multi_app/ con la URL base completa
        return str_replace('/multi_app', $baseUrl, $imagen);
    }
    
    // Si la imagen empieza con /projects/multi_app/, convertir a ruta relativa y construir URL completa
    if (strpos($imagen, '/projects/multi_app/') === 0) {
        // Remover /projects/multi_app/ del inicio para obtener la ruta relativa
        $relativePath = substr($imagen, strlen('/projects/multi_app'));
        return $baseUrl . $relativePath;
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