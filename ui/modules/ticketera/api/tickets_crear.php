<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../controllers/TicketeraController.php';
require_once __DIR__ . '/../../../controllers/AuthController.php';

try{
    $auth = new AuthController();
    $auth->requireModule('ticketera');
    $c = new TicketeraController();
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) { $input = $_POST; }
    $res = $c->tickets_crear($input);
    echo json_encode($res);
}catch(Throwable $e){
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}


