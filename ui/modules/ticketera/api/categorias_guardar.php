<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../models/TicketeraCategoria.php';
require_once __DIR__ . '/../../../controllers/AuthController.php';

try{
    $auth = new AuthController();
    $auth->requireModule('ticketera');
    $m = new TicketeraCategoria();
    if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
    } else {
        $input = $_POST;
    }
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    $nombre = trim($input['nombre'] ?? '');
    $descripcion = $input['descripcion'] ?? null;
    $estado = isset($input['estado_activo']) ? (bool)$input['estado_activo'] : true;
    $res = $id>0 ? $m->actualizar($id,$nombre,$descripcion,$estado) : $m->crear($nombre,$descripcion,$estado);
    echo json_encode($res);
}catch(Throwable $e){
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}


