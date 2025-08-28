<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../controllers/BoleteriaController.php';
require_once __DIR__ . '/../../../controllers/AuthController.php';

try {
    $auth = new AuthController();
    $auth->requireModule('boleteria.boletas');
    $c = new BoleteriaController();
    // Soporte multipart/form-data para archivo opcional
    if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data') !== false) {
        $input = $_POST;
        $archivoRuta = null;
        if (!empty($_FILES['archivo']) && is_uploaded_file($_FILES['archivo']['tmp_name'])) {
            $ext = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
            $permitidos = ['jpg','jpeg','png','pdf'];
            if (!in_array($ext, $permitidos, true)) { throw new Exception('Tipo de archivo no permitido'); }
            $destDir = __DIR__ . '/../../../assets/uploads/boletas';
            if (!is_dir($destDir)) { @mkdir($destDir, 0775, true); }
            $safeName = preg_replace('/[^a-zA-Z0-9_\.-]/','_', basename($_FILES['archivo']['name']));
            $filename = date('Ymd_His') . '_' . $safeName;
            $destPath = $destDir . '/' . $filename;
            if (!move_uploaded_file($_FILES['archivo']['tmp_name'], $destPath)) { throw new Exception('No se pudo guardar el archivo'); }
            // Ruta web relativa
            $baseUrl = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? ''))), '/'); // /ui
            $archivoRuta = 'assets/uploads/boletas/' . $filename;
            $input['archivo_ruta'] = $archivoRuta;
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


