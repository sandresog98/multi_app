<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/database.php';

header('Content-Type: application/json');
try{
  $auth = new AuthController();
  $auth->requireModule('tienda.inventario');
  $pdo = getConnection();
  $pid = (int)($_GET['producto_id'] ?? 0);
  if ($pid<=0) throw new Exception('Producto inválido');
  // Lotes de compra (sin IMEI) con cantidades residuales
  $lotes = $pdo->prepare('SELECT cd.id, cd.compra_id, cd.cantidad, cd.precio_compra, cd.precio_venta_sugerido,
                                 (cd.cantidad - COALESCE(v.sum_vendida,0)) AS disponible
                          FROM tienda_compra_detalle cd
                          LEFT JOIN (
                            SELECT compra_imei_id, producto_id, SUM(CASE WHEN compra_imei_id IS NULL THEN cantidad ELSE 1 END) AS sum_vendida
                            FROM tienda_venta_detalle WHERE producto_id = ? GROUP BY compra_imei_id, producto_id
                          ) v ON v.compra_imei_id IS NULL AND v.producto_id = cd.producto_id
                          WHERE cd.producto_id = ?
                          ORDER BY cd.id DESC');
  $lotes->execute([$pid, $pid]);
  $rows = $lotes->fetchAll(PDO::FETCH_ASSOC) ?: [];

  // IMEIs disponibles por producto con su precio
  $imeis = $pdo->prepare('SELECT ci.imei, cd.precio_compra, cd.precio_venta_sugerido, cd.compra_id
                          FROM tienda_compra_imei ci
                          INNER JOIN tienda_compra_detalle cd ON cd.id = ci.compra_detalle_id
                          WHERE ci.vendido = FALSE AND cd.producto_id = ?
                          ORDER BY ci.id DESC');
  $imeis->execute([$pid]);
  $imeisRows = $imeis->fetchAll(PDO::FETCH_ASSOC) ?: [];

  // Reversiones realizadas sobre este producto (incluye IMEI y precio de compra cuando aplica)
  $rev = $pdo->prepare('SELECT r.id, r.fecha_creacion,
                               vd.id AS venta_detalle_id, v.id AS venta_id,
                               ci.imei, cd.precio_compra, cd.precio_venta_sugerido
                        FROM tienda_reversion r
                        INNER JOIN tienda_venta_detalle vd ON vd.id = r.venta_detalle_id
                        INNER JOIN tienda_venta v ON v.id = vd.venta_id
                        LEFT JOIN tienda_compra_imei ci ON ci.id = vd.compra_imei_id
                        LEFT JOIN tienda_compra_detalle cd ON cd.id = ci.compra_detalle_id
                        WHERE vd.producto_id = ?
                        ORDER BY r.id DESC');
  $rev->execute([$pid]);
  $revRows = $rev->fetchAll(PDO::FETCH_ASSOC) ?: [];

  echo json_encode(['success'=>true,'lotes'=>$rows,'imeis'=>$imeisRows,'reversiones'=>$revRows]);
}catch(Throwable $e){ http_response_code(400); echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }


