<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../models/Ticketera.php';
require_once __DIR__ . '/../../../controllers/AuthController.php';

try{
    $auth = new AuthController();
    $auth->requireModule('ticketera');
    $user = $auth->getCurrentUser();
    $usuarioId = (int)($user['id'] ?? 0);
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) { $input = $_POST; }
    $ticketId = (int)($input['ticket_id'] ?? 0);
    $estado = trim($input['estado'] ?? '');
    $comentario = trim($input['comentario'] ?? '');
    if ($ticketId<=0 || $estado==='') throw new Exception('Datos inválidos');
    $model = new Ticketera();
    $res = $model->cambiarEstado($ticketId, $usuarioId, $estado, $comentario);
    echo json_encode($res);
}catch(Throwable $e){
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}


