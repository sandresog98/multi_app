<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/database.php';

header('Content-Type: application/json');
try {
  $auth = new AuthController();
  $auth->requireModule('creditos');
  $pdo = getConnection();
  $porTipo = $pdo->query("SELECT tipo, COUNT(*) as cantidad FROM creditos_solicitudes GROUP BY tipo")->fetchAll(PDO::FETCH_ASSOC);
  $porEstado = $pdo->query("SELECT estado, COUNT(*) as cantidad FROM creditos_solicitudes GROUP BY estado")->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode(['success'=>true,'por_tipo'=>$porTipo,'por_estado'=>$porEstado]);
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}


