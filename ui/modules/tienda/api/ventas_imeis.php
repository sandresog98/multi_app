<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/database.php';

header('Content-Type: application/json');
try{
  $auth = new AuthController();
  $auth->requireModule('tienda.ventas');
  $pdo = getConnection();
  $pid = (int)($_GET['producto_id'] ?? 0);
  if ($pid<=0) throw new Exception('Producto inválido');
  $stmt = $pdo->prepare('SELECT ci.id, ci.imei, cd.precio_compra, cd.precio_venta_sugerido, cd.compra_id
                          FROM tienda_compra_imei ci
                          INNER JOIN tienda_compra_detalle cd ON cd.id = ci.compra_detalle_id
                          WHERE ci.vendido = FALSE AND cd.producto_id = ?
                          ORDER BY ci.id DESC');
  $stmt->execute([$pid]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
  echo json_encode(['success'=>true,'items'=>$rows]);
}catch(Throwable $e){ http_response_code(400); echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }


