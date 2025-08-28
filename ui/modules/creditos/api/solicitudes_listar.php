<?php
require_once '../../../controllers/AuthController.php';
require_once '../controllers/CreditosController.php';

header('Content-Type: application/json');
try {
  $auth = new AuthController();
  $auth->requireModule('creditos');
  $c = new CreditosController();
  $page = (int)($_GET['page'] ?? 1);
  $limit = (int)($_GET['limit'] ?? 20);
  $filters = [ 'q'=>$_GET['q'] ?? '', 'estado'=>$_GET['estado'] ?? '' ];
  $sortBy = $_GET['sort_by'] ?? 'fecha_creacion';
  $sortDir = $_GET['sort_dir'] ?? 'DESC';
  echo json_encode(['success'=>true,'data'=>$c->listar($page,$limit,$filters,$sortBy,$sortDir)]);
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}


