<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../models/TicketeraCategoria.php';
require_once __DIR__ . '/../../../controllers/AuthController.php';

try{
    $auth = new AuthController();
    $auth->requireModule('ticketera');
    $m = new TicketeraCategoria();
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 20);
    $search = trim($_GET['q'] ?? '');
    $estado = $_GET['estado'] ?? '';
    echo json_encode(['success'=>true,'data'=>$m->listar($page,$limit,$search,$estado)]);
}catch(Throwable $e){
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}


