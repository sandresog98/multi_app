<?php
require_once '../../../controllers/AuthController.php';
require_once '../models/Comision.php';

header('Content-Type: application/json');

try {
  $auth = new AuthController();
  $auth->requireModule('oficina.comisiones');
  
  $page = max(1, (int)($_GET['page'] ?? 1));
  $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
  
  $filtros = [];
  if (!empty($_GET['asociado_inicial'])) {
    $filtros['asociado_inicial'] = trim($_GET['asociado_inicial']);
  }
  if (!empty($_GET['asociado_referido'])) {
    $filtros['asociado_referido'] = trim($_GET['asociado_referido']);
  }
  if (!empty($_GET['fecha_desde'])) {
    $filtros['fecha_desde'] = trim($_GET['fecha_desde']);
  }
  if (!empty($_GET['fecha_hasta'])) {
    $filtros['fecha_hasta'] = trim($_GET['fecha_hasta']);
  }

  $model = new Comision();
  $data = $model->listar($page, $limit, $filtros);
  
  echo json_encode([
    'success' => true,
    'comisiones' => $data['comisiones'] ?? [],
    'total' => $data['total'] ?? 0,
    'pages' => $data['pages'] ?? 1,
    'current_page' => $data['current_page'] ?? 1
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
