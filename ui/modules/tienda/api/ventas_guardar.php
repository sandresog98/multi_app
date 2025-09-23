<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/database.php';

header('Content-Type: application/json');
try{
  $auth = new AuthController();
  $auth->requireModule('tienda.ventas');
  $user = $auth->getCurrentUser();
  $pdo = getConnection();
  $input = json_decode(file_get_contents('php://input'), true);
  if (!$input) throw new Exception('Entrada inválida');
  $tipo = $input['tipo_cliente'] ?? '';
  if (!in_array($tipo,['asociado','externo'],true)) throw new Exception('Tipo cliente inválido');
  $items = $input['items'] ?? []; $pagos = $input['pagos'] ?? [];
  if (!$items || !is_array($items)) throw new Exception('Sin items');

  $pdo->beginTransaction();
  $stmt = $pdo->prepare('INSERT INTO tienda_venta (tipo_cliente, asociado_cedula, cliente_id, usuario_id, total) VALUES (?,?,?,?,0)');
  $stmt->execute([$tipo, $tipo==='asociado'?($input['asociado_cedula'] ?? null):null, $tipo==='externo'?(int)($input['cliente_id'] ?? 0):null, (int)($user['id'] ?? 0)]);
  $ventaId = (int)$pdo->lastInsertId();
  $detStmt = $pdo->prepare('INSERT INTO tienda_venta_detalle (venta_id, producto_id, cantidad, precio_unitario, subtotal, compra_imei_id) VALUES (?,?,?,?,?,?)');

  $total = 0;
  foreach ($items as $it){
    $pid = (int)($it['producto_id'] ?? 0); $cant=(int)($it['cantidad'] ?? 0); $precio=(float)($it['precio_unitario'] ?? 0);
    if ($pid<=0 || $cant<=0) throw new Exception('Item inválido');
    $imeis = $it['imeis'] ?? [];
    // Validar stock disponible en servidor
    if ($imeis && count($imeis)){
      $q = $pdo->prepare('SELECT COUNT(*) FROM tienda_compra_imei ci INNER JOIN tienda_compra_detalle cd ON cd.id=ci.compra_detalle_id WHERE ci.vendido=FALSE AND cd.producto_id=?');
      $q->execute([$pid]);
      $dispI = (int)$q->fetchColumn();
      if ($cant > $dispI) throw new Exception('Stock IMEI insuficiente para el producto');
    } else {
      // Calcular disponible evitando duplicar la suma de vendidos por múltiples filas de compras
      $q = $pdo->prepare('SELECT 
          (SELECT COALESCE(SUM(cantidad),0) FROM tienda_compra_detalle WHERE producto_id = ?) 
        - (SELECT COALESCE(SUM(cantidad),0) FROM tienda_venta_detalle WHERE producto_id = ? AND compra_imei_id IS NULL) AS disp');
      $q->execute([$pid, $pid]);
      $disp = (int)$q->fetchColumn();
      if ($cant > $disp) throw new Exception('Stock insuficiente para el producto');
    }
    if ($imeis && count($imeis)){
      // Validar IMEIs disponibles (no vendidos)
      foreach ($imeis as $imei){
        $q = $pdo->prepare('SELECT ci.id FROM tienda_compra_imei ci INNER JOIN tienda_compra_detalle cd ON cd.id=ci.compra_detalle_id WHERE ci.vendido=FALSE AND ci.imei=? AND cd.producto_id=? LIMIT 1');
        $q->execute([(string)$imei, $pid]); $row=$q->fetch(); if(!$row) throw new Exception('IMEI no disponible: '.$imei);
        $detStmt->execute([$ventaId,$pid,1,$precio,$precio,(int)$row['id']]);
        $upd=$pdo->prepare('UPDATE tienda_compra_imei SET vendido=TRUE WHERE id=?'); $upd->execute([(int)$row['id']]);
        $total += $precio;
      }
    } else {
      $detStmt->execute([$ventaId,$pid,$cant,$precio,$cant*$precio,null]);
      $total += $cant*$precio;
    }
  }

  // Pagos
  $payStmt = $pdo->prepare('INSERT INTO tienda_venta_pago (venta_id, tipo, monto, numero_credito_sifone, pago_anterior_id) VALUES (?,?,?,?,?)');
  $sumPagos = 0;
  foreach ($pagos as $p){
    $tipo = $p['tipo'] ?? ''; $monto = (float)($p['monto'] ?? 0);
    if (!in_array($tipo,['efectivo','bold','qr','sifone','reversion'],true)) throw new Exception('Tipo de pago inválido');
    if ($monto<=0) throw new Exception('Monto de pago inválido');
    $num = $p['numero_credito_sifone'] ?? null; $ant = $p['pago_anterior_id'] ?? null;
    if ($tipo==='sifone' && !preg_match('/^\d+$/', (string)$num)) throw new Exception('Número crédito SIFONE inválido');
    if ($tipo==='reversion' && !$ant) throw new Exception('Pago anterior requerido para reversion');
    $payStmt->execute([$ventaId,$tipo,$monto,$num,$ant]); $sumPagos += $monto;
  }

  // Actualizar total
  $up = $pdo->prepare('UPDATE tienda_venta SET total=? WHERE id=?'); $up->execute([$total, $ventaId]);
  if (abs($sumPagos - $total) > 0.01) {
    // Permitimos pagos parciales? Por ahora no: exigir coincidencia
    throw new Exception('La suma de pagos no coincide con el total');
  }

  $pdo->commit();
  echo json_encode(['success'=>true,'id'=>$ventaId]);
}catch(Throwable $e){ if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack(); http_response_code(400); echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }


