<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/database.php';

header('Content-Type: application/json');
try{
  $auth = new AuthController();
  $auth->requireModule('tienda.compras');
  $pdo = getConnection();
  $rows = $pdo->query('SELECT c.id, c.fecha_creacion, COALESCE(SUM(cd.cantidad),0) AS total_cantidad, COUNT(cd.id) AS items FROM tienda_compra c LEFT JOIN tienda_compra_detalle cd ON cd.compra_id=c.id GROUP BY c.id ORDER BY c.id DESC LIMIT 50')->fetchAll(PDO::FETCH_ASSOC) ?: [];

  // Precalcular ventas sin IMEI por producto
  $ventas = $pdo->query('SELECT producto_id, COALESCE(SUM(cantidad),0) cant FROM tienda_venta_detalle WHERE compra_imei_id IS NULL GROUP BY producto_id')->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

  foreach ($rows as &$r){
    $compraId = (int)$r['id'];
    // Si hay algún IMEI vendido de esta compra => no eliminable
    $qImei = $pdo->prepare('SELECT COUNT(*) FROM tienda_compra_imei ci INNER JOIN tienda_compra_detalle cd ON cd.id=ci.compra_detalle_id WHERE cd.compra_id = ? AND ci.vendido = TRUE');
    $qImei->execute([$compraId]);
    $hayImeiVendido = (int)$qImei->fetchColumn() > 0;
    $deletable = !$hayImeiVendido;
    if ($deletable){
      // Verificar cobertura para productos sin IMEI
      $det = $pdo->prepare('SELECT producto_id, cantidad FROM tienda_compra_detalle WHERE compra_id = ?');
      $det->execute([$compraId]);
      $dets = $det->fetchAll(PDO::FETCH_ASSOC) ?: [];
      foreach ($dets as $d){
        $pid = (int)$d['producto_id']; $qty = (int)$d['cantidad'];
        // Cantidad de compras de otros registros para el producto
        $qOtros = $pdo->prepare('SELECT COALESCE(SUM(cantidad),0) FROM tienda_compra_detalle WHERE producto_id = ? AND compra_id <> ?');
        $qOtros->execute([$pid, $compraId]);
        $otros = (int)$qOtros->fetchColumn();
        $vendidoSinImei = (int)($ventas[$pid] ?? 0);
        // Si al quitar esta compra quedaría negativo, no se puede eliminar
        if (($otros - $vendidoSinImei) < 0) { $deletable = false; break; }
      }
    }
    $r['deletable'] = $deletable;
  }
  unset($r);

  echo json_encode(['success'=>true,'items'=>$rows]);
}catch(Throwable $e){ http_response_code(400); echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }


