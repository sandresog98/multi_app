<?php
require_once '../../../controllers/AuthController.php';
require_once '../models/Comision.php';

header('Content-Type: application/json');

try {
  $auth = new AuthController();
  $auth->requireModule('oficina.comisiones');
  
  $id = (int)($_GET['id'] ?? 0);
  if ($id <= 0) {
    echo json_encode(['success'=>false,'message'=>'ID de comisión inválido']);
    exit;
  }

  $model = new Comision();
  $comision = $model->obtenerPorId($id);
  
  if (!$comision) {
    echo json_encode(['success'=>false,'message'=>'Comisión no encontrada']);
    exit;
  }
  
  echo json_encode(['success' => true, 'comision' => $comision]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
