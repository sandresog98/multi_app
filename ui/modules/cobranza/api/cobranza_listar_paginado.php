<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../models/Cobranza.php';
require_once __DIR__ . '/../../../controllers/AuthController.php';

try {
  $auth = new AuthController();
  $auth->requireModule('cobranza.comunicaciones');
  $m = new Cobranza();
  $page = (int)($_GET['page'] ?? 1);
  $limit = (int)($_GET['limit'] ?? 20);
  $sortBy = $_GET['sort_by'] ?? 'max_diav';
  $sortDir = $_GET['sort_dir'] ?? 'DESC';
  $filtros = [
    'q' => $_GET['q'] ?? '',
    'estado' => $_GET['estado'] ?? '',
    'rango' => $_GET['rango'] ?? ''
  ];
  $data = $m->listarAsociadosConMoraPaginado($filtros, $page, $limit, $sortBy, $sortDir);
  echo json_encode(['success'=>true,'data'=>$data]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}


