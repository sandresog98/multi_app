<?php
require_once '../../../controllers/AuthController.php';
require_once '../models/Cargas.php';
require_once '../../../models/Logger.php';

header('Content-Type: application/json');

try {
    $auth = new AuthController();
    $auth->requireModule('oficina.cargas');
    $user = $auth->getCurrentUser();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Método no permitido');
    if (empty($_FILES['archivo']['name'])) throw new Exception('Archivo requerido');
    if (($_FILES['archivo']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new Exception('Error en subida (código: '.(int)$_FILES['archivo']['error'].')');
    }
    $tipo = $_POST['tipo'] ?? '';
    if (!$tipo) throw new Exception('Tipo requerido');

    $allowed = ['sifone_libro','sifone_cartera_aseguradora','sifone_cartera_mora','sifone_datacredito','pagos_pse','pagos_confiar'];
    if (!in_array($tipo, $allowed, true)) throw new Exception('Tipo inválido');

    $ext = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['xls','xlsx'], true)) throw new Exception('Extensión no permitida');

    // Resolver directorio destino (crear si no existe)
    if (strpos($tipo, 'sifone_') === 0) {
        $destDir = __DIR__ . '/../../../../py/data/sifone';
    } else if ($tipo === 'pagos_pse') {
        $destDir = __DIR__ . '/../../../../py/data/pagos/pse';
    } else if ($tipo === 'pagos_confiar') {
        $destDir = __DIR__ . '/../../../../py/data/pagos/confiar';
    } else {
        throw new Exception('Directorio destino no disponible');
    }
    if (!is_dir($destDir)) {
        if (!@mkdir($destDir, 0775, true)) {
            throw new Exception('No se pudo crear el directorio destino: '.$destDir);
        }
    }
    if (!is_writable($destDir)) {
        throw new Exception('Directorio no escribible: '.$destDir);
    }

    $safeName = uniqid($tipo . '_', true) . '.' . $ext;
    $destPath = $destDir . DIRECTORY_SEPARATOR . $safeName;
    if (!is_uploaded_file($_FILES['archivo']['tmp_name'])) {
        throw new Exception('Archivo temporal no válido');
    }
    if (!move_uploaded_file($_FILES['archivo']['tmp_name'], $destPath)) {
        throw new Exception('No se pudo mover el archivo a: '.$destPath);
    }

    // Guardar job
    $m = new Cargas();
    $id = $m->crearJob($tipo, $destPath, (int)($user['id'] ?? null));

    // Log de creación de carga
    $logger = new Logger();
    $logger->logCrear('cargas', 'Creación de job de carga', [
        'control_cargas_id' => $id,
        'tipo' => $tipo,
        'archivo' => basename($destPath)
    ]);

    echo json_encode(['success'=>true, 'id'=>$id]);
} catch (Throwable $e) {
    // Log de error
    try { (new Logger())->logEditar('cargas', null, null, ['error' => $e->getMessage()]); } catch (Throwable $ignored) {}
    http_response_code(400);
    echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
}

