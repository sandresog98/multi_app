<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/database.php';

header('Content-Type: application/json');
try{
  $auth = new AuthController();
  $auth->requireModule('tienda.compras');
  $pdo = getConnection();
  $id = (int)($_POST['id'] ?? 0);
  if ($id<=0) throw new Exception('ID inválido');

  // Bloquear si hay IMEI vendido en esta compra
  $qImei = $pdo->prepare('SELECT COUNT(*) FROM tienda_compra_imei ci INNER JOIN tienda_compra_detalle cd ON cd.id=ci.compra_detalle_id WHERE cd.compra_id = ? AND ci.vendido = TRUE');
  $qImei->execute([$id]);
  if ((int)$qImei->fetchColumn() > 0) throw new Exception('No se puede eliminar: hay IMEIs vendidos de esta compra');

  $pdo->beginTransaction();
  // Borrar IMEIs
  $delImeis = $pdo->prepare('DELETE ci FROM tienda_compra_imei ci INNER JOIN tienda_compra_detalle cd ON cd.id=ci.compra_detalle_id WHERE cd.compra_id = ?');
  $delImeis->execute([$id]);
  // Borrar detalle y cabecera
  $pdo->prepare('DELETE FROM tienda_compra_detalle WHERE compra_id = ?')->execute([$id]);
  $pdo->prepare('DELETE FROM tienda_compra WHERE id = ?')->execute([$id]);
  $pdo->commit();
  echo json_encode(['success'=>true]);
}catch(Throwable $e){ if(isset($pdo)&&$pdo->inTransaction()) $pdo->rollBack(); http_response_code(400); echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }


