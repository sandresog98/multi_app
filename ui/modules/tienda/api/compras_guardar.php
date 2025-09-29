<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/database.php';
require_once '../../../models/Logger.php';

header('Content-Type: application/json');
try{
  $auth = new AuthController();
  $auth->requireModule('tienda.compras');
  $user = $auth->getCurrentUser();
  $pdo = getConnection();
  $input = json_decode(file_get_contents('php://input'), true);
  if (!$input || !is_array($input['items'] ?? null) || !count($input['items'])) throw new Exception('Datos inválidos');

  $pdo->beginTransaction();
  $stmt = $pdo->prepare('INSERT INTO tienda_compra (usuario_id, observacion) VALUES (?, NULL)');
  $stmt->execute([(int)($user['id'] ?? 0)]);
  $compraId = (int)$pdo->lastInsertId();

  $detStmt = $pdo->prepare('INSERT INTO tienda_compra_detalle (compra_id, producto_id, cantidad, precio_compra, precio_venta_sugerido) VALUES (?,?,?,?,?)');
  $imeiStmt = $pdo->prepare('INSERT INTO tienda_compra_imei (compra_detalle_id, imei) VALUES (?,?)');

  foreach ($input['items'] as $it){
    $pid = (int)($it['producto_id'] ?? 0);
    $cant = (int)($it['cantidad'] ?? 0);
    $pc = (float)($it['precio_compra'] ?? 0);
    $pv = (float)($it['precio_venta_sugerido'] ?? 0);
    if ($pid<=0 || $cant<=0) throw new Exception('Item inválido');
    $detStmt->execute([$compraId,$pid,$cant,$pc,$pv]);
    $detId = (int)$pdo->lastInsertId();

    $imeis = $it['imeis'] ?? [];
    if ($imeis && count($imeis)){ foreach ($imeis as $imei){ $imeiStmt->execute([$detId, (string)$imei]); } }
  }

  $pdo->commit();
  
  // Log de creación de compra
  try {
    (new Logger())->logCrear('tienda.compras', 'Crear compra', [
      'id' => $compraId,
      'usuario_id' => (int)($user['id'] ?? 0),
      'items_count' => count($input['items']),
      'total_imeis' => array_sum(array_map(function($item) { return count($item['imeis'] ?? []); }, $input['items']))
    ]);
  } catch (Throwable $ignored) {}
  
  echo json_encode(['success'=>true,'id'=>$compraId]);
}catch(Throwable $e){ 
  if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack(); 
  try { (new Logger())->logEditar('tienda.compras', 'Error al crear compra', null, ['error' => $e->getMessage()]); } catch (Throwable $ignored) {}
  http_response_code(400); 
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]); 
}


