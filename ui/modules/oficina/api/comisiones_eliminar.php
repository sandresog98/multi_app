<?php
require_once '../../../controllers/AuthController.php';
require_once '../models/Comision.php';
require_once '../../../models/Logger.php';

header('Content-Type: application/json');

try {
  $auth = new AuthController();
  $auth->requireModule('oficina.comisiones');
  $user = $auth->getCurrentUser();

  if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Método no permitido']);
    exit;
  }

  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) {
    echo json_encode(['success'=>false,'message'=>'ID de comisión inválido']);
    exit;
  }

  // Obtener datos antes de eliminar para el log
  $model = new Comision();
  $comision = $model->obtenerPorId($id);
  
  if (!$comision) {
    echo json_encode(['success'=>false,'message'=>'Comisión no encontrada']);
    exit;
  }

  $res = $model->eliminar($id);

  if ($res['success'] ?? false) {
    (new Logger())->logEliminar('oficina.comisiones', 'Eliminación de comisión', [
      'id' => $id,
      'asociado_inicial' => $comision['asociado_inicial_cedula'],
      'asociado_referido' => $comision['asociado_referido_cedula'],
      'fecha' => $comision['fecha_comision'],
      'valor' => $comision['valor_ganado']
    ]);
    echo json_encode(['success'=>true]);
  } else {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>$res['message'] ?? 'No se pudo eliminar']);
  }
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
