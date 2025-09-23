<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/database.php';

header('Content-Type: application/json');
try{
  $auth = new AuthController();
  $auth->requireModule('tienda.compras');
  $pdo = getConnection();
  $id = (int)($_GET['id'] ?? 0);
  if ($id<=0) throw new Exception('ID inválido');
  $cab = $pdo->prepare('SELECT id, usuario_id, fecha_creacion, observacion FROM tienda_compra WHERE id=?');
  $cab->execute([$id]);
  $head = $cab->fetch(PDO::FETCH_ASSOC);
  if (!$head) throw new Exception('Compra no encontrada');
  $det = $pdo->prepare('SELECT cd.id, p.nombre AS producto, c.nombre AS categoria, cd.cantidad, cd.precio_compra, cd.precio_venta_sugerido
                        FROM tienda_compra_detalle cd
                        INNER JOIN tienda_producto p ON p.id = cd.producto_id
                        INNER JOIN tienda_categoria c ON c.id = p.categoria_id
                        WHERE cd.compra_id = ?
                        ORDER BY c.nombre, p.nombre');
  $det->execute([$id]);
  $detalles = $det->fetchAll(PDO::FETCH_ASSOC) ?: [];
  if ($detalles){
    $imeiq = $pdo->prepare('SELECT imei FROM tienda_compra_imei WHERE compra_detalle_id = ? ORDER BY id');
    foreach ($detalles as &$d){
      $imeiq->execute([(int)$d['id']]);
      $d['imeis'] = array_column($imeiq->fetchAll(PDO::FETCH_ASSOC) ?: [], 'imei');
    }
    unset($d);
  }
  echo json_encode(['success'=>true,'cabecera'=>$head,'detalles'=>$detalles]);
}catch(Throwable $e){ http_response_code(400); echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }


