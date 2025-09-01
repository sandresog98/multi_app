<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../controllers/TicketeraController.php';
require_once __DIR__ . '/../../../controllers/AuthController.php';

try{
    $auth = new AuthController();
    $auth->requireModule('ticketera');
    $c = new TicketeraController();
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 10);
    $filters = [
        'q' => $_GET['q'] ?? '',
        'estado' => $_GET['estado'] ?? '',
        'responsable' => $_GET['responsable'] ?? ''
    ];
    $sortBy = $_GET['sort_by'] ?? 'fecha_creacion';
    $sortDir = $_GET['sort_dir'] ?? 'DESC';
    echo json_encode(['success'=>true,'data'=>$c->tickets_listar($page,$limit,$filters,$sortBy,$sortDir)]);
}catch(Throwable $e){
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}


