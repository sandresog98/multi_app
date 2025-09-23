<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/database.php';

header('Content-Type: application/json');
try{
  $auth = new AuthController();
  $auth->requireModule('tienda.facturacion');
  $pdo = getConnection();
  $id = (int)($_POST['id'] ?? 0);
  if ($id<=0) throw new Exception('ID inválido');
  $pdo->beginTransaction();
  // Liberar IMEIs vendidos en esta venta
  $q = $pdo->prepare('SELECT compra_imei_id FROM tienda_venta_detalle WHERE venta_id = ? AND compra_imei_id IS NOT NULL');
  $q->execute([$id]);
  foreach ($q->fetchAll(PDO::FETCH_COLUMN) as $imeiId){ $pdo->prepare('UPDATE tienda_compra_imei SET vendido = FALSE WHERE id = ?')->execute([(int)$imeiId]); }
  // Borrar pagos, detalle, cabecera
  $pdo->prepare('DELETE FROM tienda_venta_pago WHERE venta_id = ?')->execute([$id]);
  $pdo->prepare('DELETE FROM tienda_venta_detalle WHERE venta_id = ?')->execute([$id]);
  $pdo->prepare('DELETE FROM tienda_venta WHERE id = ?')->execute([$id]);
  $pdo->commit();
  echo json_encode(['success'=>true]);
}catch(Throwable $e){ if(isset($pdo)&&$pdo->inTransaction()) $pdo->rollBack(); http_response_code(400); echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }


