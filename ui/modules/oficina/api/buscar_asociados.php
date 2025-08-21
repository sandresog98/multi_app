<?php
require_once '../../../controllers/AuthController.php';
require_once '../models/Asociado.php';
header('Content-Type: application/json');

try {
  $auth = new AuthController();
  $auth->requireAnyRole(['admin','oficina']);
  $q = trim($_GET['q'] ?? '');
  if (strlen($q) < 2) { echo json_encode(['success'=>true,'items'=>[]]); exit; }
  $m = new Asociado();
  // Reusar getAsociados con límite pequeño
  $data = $m->getAsociados(1, 10, $q, '');
  $items = array_map(function($r){
    return ['cedula'=>$r['cedula'], 'nombre'=>$r['nombre']];
  }, $data['asociados'] ?? []);
  echo json_encode(['success'=>true,'items'=>$items]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
