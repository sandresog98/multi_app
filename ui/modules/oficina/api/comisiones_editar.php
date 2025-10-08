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

  $asociadoInicial = trim($_POST['asociado_inicial'] ?? '');
  $asociadoReferido = trim($_POST['asociado_referido'] ?? '');
  $fechaComision = trim($_POST['fecha_comision'] ?? '');
  $valorGanado = trim($_POST['valor_ganado'] ?? '');
  $observaciones = trim($_POST['observaciones'] ?? '');

  // Normalizar fecha (YYYY-MM-DD)
  if ($fechaComision !== '') {
    $ts = strtotime($fechaComision);
    if ($ts === false) { 
      echo json_encode(['success'=>false,'message'=>'Fecha de comisión inválida']); 
      exit; 
    }
    $fechaComision = date('Y-m-d', $ts);
  }

  // Normalizar valor
  $valorNum = (float)preg_replace('/[^0-9.-]/','', $valorGanado);

  $model = new Comision();
  $res = $model->editar($id, $asociadoInicial, $asociadoReferido, $fechaComision, $valorNum, $observaciones);

  if ($res['success'] ?? false) {
    (new Logger())->logEditar('oficina.comisiones', 'Edición de comisión', 
      ['id' => $id], 
      ['id' => $id, 'asociado_inicial' => $asociadoInicial, 'asociado_referido' => $asociadoReferido, 'fecha' => $fechaComision, 'valor' => $valorNum]
    );
    echo json_encode(['success'=>true]);
  } else {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>$res['message'] ?? 'No se pudo actualizar']);
  }
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
