<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/database.php';

header('Content-Type: application/json');
try{
  $auth = new AuthController();
  $auth->requireModule('tienda.facturacion');
  $pdo = getConnection();
  $id = (int)($_GET['id'] ?? 0);
  if ($id<=0) throw new Exception('ID inválido');
  $cab = $pdo->prepare('SELECT v.*, c.nombre AS cliente_nombre, c.nit_cedula AS cliente_doc FROM tienda_venta v LEFT JOIN tienda_clientes c ON c.id=v.cliente_id WHERE v.id=?');
  $cab->execute([$id]);
  $venta = $cab->fetch(PDO::FETCH_ASSOC);
  if (!$venta) throw new Exception('Venta no encontrada');
  $det = $pdo->prepare('SELECT vd.*, p.nombre AS producto, cat.nombre AS categoria,
                               ci.imei,
                               (
                                 CASE 
                                   WHEN vd.compra_imei_id IS NOT NULL THEN cd.precio_compra
                                   ELSE (
                                     SELECT cd2.precio_compra
                                     FROM tienda_compra_detalle cd2
                                     INNER JOIN tienda_compra c2 ON c2.id = cd2.compra_id
                                     WHERE cd2.producto_id = vd.producto_id AND c2.fecha_creacion <= v.fecha_creacion
                                     ORDER BY c2.fecha_creacion DESC, cd2.id DESC
                                     LIMIT 1
                                   )
                                 END
                               ) AS precio_compra
                        FROM tienda_venta_detalle vd
                        INNER JOIN tienda_venta v ON v.id = vd.venta_id
                        INNER JOIN tienda_producto p ON p.id=vd.producto_id
                        INNER JOIN tienda_categoria cat ON cat.id=p.categoria_id
                        LEFT JOIN tienda_compra_imei ci ON ci.id = vd.compra_imei_id
                        LEFT JOIN tienda_compra_detalle cd ON cd.id = ci.compra_detalle_id
                        WHERE vd.venta_id=?
                        ORDER BY vd.id');
  $det->execute([$id]);
  $detalles = $det->fetchAll(PDO::FETCH_ASSOC) ?: [];
  $pagos = $pdo->prepare('SELECT tipo, monto, numero_credito_sifone, pago_anterior_id FROM tienda_venta_pago WHERE venta_id=? ORDER BY id');
  $pagos->execute([$id]);
  $pagosRows = $pagos->fetchAll(PDO::FETCH_ASSOC) ?: [];
  // Marcar items revertidos
  if ($detalles){
    $qrev = $pdo->prepare('SELECT venta_detalle_id FROM tienda_reversion WHERE venta_detalle_id IN (' . implode(',', array_fill(0,count($detalles),'?')) . ')');
    $ids = array_map(fn($d)=> (int)$d['id'], $detalles);
    $qrev->execute($ids);
    $revMap = array_flip($qrev->fetchAll(PDO::FETCH_COLUMN) ?: []);
    foreach ($detalles as &$d){ $d['revertido'] = isset($revMap[(int)$d['id']]); }
    unset($d);
  }
  echo json_encode(['success'=>true,'venta'=>$venta,'detalles'=>$detalles,'pagos'=>$pagosRows]);
}catch(Throwable $e){ http_response_code(400); echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }


