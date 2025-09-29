<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../controllers/BoleteriaController.php';
require_once __DIR__ . '/../../../controllers/AuthController.php';
require_once __DIR__ . '/../../../models/Logger.php';

try {
    $auth = new AuthController();
    $auth->requireModule('boleteria.categorias');
    $c = new BoleteriaController();
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) { $input = $_POST; }
    $id = (int)($input['id'] ?? 0);
    if ($id > 0) {
        $res = $c->categorias_actualizar($id, $input);
        // Log de actualización de categoría
        if ($res['success']) {
            try {
                (new Logger())->logEditar('boleteria.categorias', 'Actualizar categoría', ['id' => $id], $input);
            } catch (Throwable $ignored) {}
        }
    } else {
        $res = $c->categorias_crear($input);
        // Log de creación de categoría
        if ($res['success']) {
            try {
                (new Logger())->logCrear('boleteria.categorias', 'Crear categoría', $input);
            } catch (Throwable $ignored) {}
        }
    }
    echo json_encode($res);
} catch (Throwable $e) {
    try { (new Logger())->logEditar('boleteria.categorias', 'Error en categoría', null, ['error' => $e->getMessage()]); } catch (Throwable $ignored) {}
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}


