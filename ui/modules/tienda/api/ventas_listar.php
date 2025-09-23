<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/database.php';

header('Content-Type: application/json');
try{
  $auth = new AuthController();
  $auth->requireModule('tienda.facturacion');
  $pdo = getConnection();
  $limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 100;
  $stmt = $pdo->prepare('SELECT v.id, v.tipo_cliente, v.asociado_cedula, v.cliente_id, v.total, v.fecha_creacion,
                                (SELECT nombre FROM tienda_clientes WHERE id=v.cliente_id) AS cliente_nombre,
                                (SELECT COUNT(*) FROM tienda_reversion r INNER JOIN tienda_venta_detalle vd ON vd.id=r.venta_detalle_id WHERE vd.venta_id=v.id) AS reversiones,
                                (SELECT COUNT(*) FROM tienda_venta_pago p_ref WHERE p_ref.pago_anterior_id IN (SELECT id FROM tienda_venta_pago WHERE venta_id=v.id)) AS usada_como_pago_anterior
                         FROM tienda_venta v ORDER BY v.id DESC LIMIT ?');
  $stmt->execute([$limit]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
  echo json_encode(['success'=>true,'items'=>$rows]);
}catch(Throwable $e){ http_response_code(400); echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }


