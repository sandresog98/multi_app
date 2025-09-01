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
    $comentario = trim($input['comentario'] ?? '');
    if ($ticketId<=0) throw new Exception('ID inválido');
    $model = new Ticketera();
    $res = $model->comentar($ticketId, $usuarioId, $comentario);
    echo json_encode($res);
}catch(Throwable $e){
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}


