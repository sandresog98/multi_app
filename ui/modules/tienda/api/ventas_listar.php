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
                                (SELECT nombre FROM tienda_clientes WHERE id=v.cliente_id) AS cliente_nombre
                         FROM tienda_venta v ORDER BY v.id DESC LIMIT ?');
  $stmt->execute([$limit]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
  echo json_encode(['success'=>true,'items'=>$rows]);
}catch(Throwable $e){ http_response_code(400); echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }


