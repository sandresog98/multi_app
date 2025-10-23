<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../models/CxPublicidad.php';
require_once __DIR__ . '/../../../controllers/AuthController.php';
require_once __DIR__ . '/../../../utils/FileUploadManager.php';

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
            
            // Agregar información de estado
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
?>