<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/database.php';

header('Content-Type: application/json');
try{
  $auth = new AuthController();
  $auth->requireModule('tienda.inventario');
  $pdo = getConnection();
  $productoId = (int)($_GET['producto_id'] ?? 0);
  if ($productoId<=0) throw new Exception('Producto inválido');
  $stmt = $pdo->prepare('SELECT ci.imei FROM tienda_compra_imei ci INNER JOIN tienda_compra_detalle cd ON cd.id=ci.compra_detalle_id WHERE ci.vendido=FALSE AND cd.producto_id = ? ORDER BY ci.id DESC');
  $stmt->execute([$productoId]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
  echo json_encode(['success'=>true,'items'=>$rows]);
}catch(Throwable $e){ http_response_code(400); echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }


