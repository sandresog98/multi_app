<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../controllers/TicketeraController.php';
require_once __DIR__ . '/../../../controllers/AuthController.php';

try{
    $auth = new AuthController();
    $auth->requireModule('ticketera');
    $c = new TicketeraController();
    $id = (int)($_GET['id'] ?? 0);
    if ($id<=0) throw new Exception('ID inválido');
    $model = new Ticketera();
    $det = $model->obtenerDetalle($id);
    if(!$det) throw new Exception('No encontrado');
    echo json_encode(['success'=>true,'data'=>$det]);
}catch(Throwable $e){
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}


