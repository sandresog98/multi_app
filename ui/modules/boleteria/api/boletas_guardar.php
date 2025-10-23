<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../controllers/BoleteriaController.php';
require_once __DIR__ . '/../../../controllers/AuthController.php';
require_once __DIR__ . '/../../../utils/FileUploadManager.php';

try {
    $auth = new AuthController();
    $auth->requireModule('boleteria.boletas');
    $c = new BoleteriaController();
    // Soporte multipart/form-data para archivo opcional usando FileUploadManager
    if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data') !== false) {
        $input = $_POST;
        $archivoRuta = null;
        
        if (!empty($_FILES['archivo']) && is_uploaded_file($_FILES['archivo']['tmp_name'])) {
            try {
                // Configurar opciones para boletería
                $options = [
                    'maxSize' => 2 * 1024 * 1024, // 2MB
                    'allowedExtensions' => ['jpg', 'jpeg', 'png', 'pdf'],
                    'prefix' => 'boleta',
                    'userId' => $auth->getCurrentUser()['id'] ?? '',
                    'webPath' => 'assets/uploads/boletas'
                ];
                
                $baseDir = __DIR__ . '/../../../assets/uploads/boletas';
                $result = FileUploadManager::saveUploadedFile($_FILES['archivo'], $baseDir, $options);
                $archivoRuta = $result['webUrl'];
                $input['archivo_ruta'] = $archivoRuta;
                
            } catch (Exception $e) {
                throw new Exception('Error guardando archivo: ' . $e->getMessage());
            }
        }
        
        $res = $c->boletas_crear($input);
    } else {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) { $input = $_POST; }
        $res = $c->boletas_crear($input);
    }
    echo json_encode($res);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}


