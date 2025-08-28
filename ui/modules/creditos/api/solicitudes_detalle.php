<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/database.php';
require_once '../../../config/paths.php';

header('Content-Type: application/json');
try{
  $auth = new AuthController();
  $auth->requireModule('creditos');
  $pdo = getConnection();
  $id = (int)($_GET['id'] ?? 0);
  if ($id<=0) throw new Exception('ID inválido');
  $stmt = $pdo->prepare("SELECT * FROM creditos_solicitudes WHERE id=?");
  $stmt->execute([$id]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$row) throw new Exception('No encontrado');
  $h = $pdo->prepare("SELECT h.*, u.usuario AS usuario FROM creditos_historial h LEFT JOIN control_usuarios u ON u.id = h.usuario_id WHERE h.solicitud_id = ? ORDER BY h.fecha DESC");
  $h->execute([$id]);
  $hist = $h->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode(['success'=>true,'item'=>$row,'historial'=>$hist]);
}catch(Throwable $e){ http_response_code(400); echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }


