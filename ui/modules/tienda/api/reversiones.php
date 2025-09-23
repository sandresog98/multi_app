<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/database.php';

header('Content-Type: application/json');
try{
  $auth = new AuthController();
  $auth->requireModule('tienda.reversiones');
  $pdo = getConnection();
  $action = $_GET['action'] ?? ($_POST['action'] ?? '');
  if ($action==='listar'){
    $rows = $pdo->query('SELECT r.id, r.venta_detalle_id, r.motivo, r.puede_revender, r.fecha_creacion,
                                p.nombre AS producto, ci.imei
                         FROM tienda_reversion r
                         LEFT JOIN tienda_venta_detalle vd ON vd.id = r.venta_detalle_id
                         LEFT JOIN tienda_producto p ON p.id = vd.producto_id
                         LEFT JOIN tienda_compra_imei ci ON ci.id = vd.compra_imei_id
                         ORDER BY r.id DESC')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    echo json_encode(['success'=>true,'items'=>$rows]); return;
  }
  if ($action==='crear'){
    $det = (int)($_POST['venta_detalle_id'] ?? 0); $motivo = (string)($_POST['motivo'] ?? ''); $rev = (int)($_POST['puede_revender'] ?? 0);
    if ($det<=0) throw new Exception('Detalle inválido');
    // Evitar duplicadas: si ya existe reversión sobre este detalle, bloquear
    $ex = $pdo->prepare('SELECT COUNT(*) FROM tienda_reversion WHERE venta_detalle_id = ?'); $ex->execute([$det]); if ((int)$ex->fetchColumn() > 0) throw new Exception('Este producto ya tiene reversión registrada');
    $stmt = $pdo->prepare('INSERT INTO tienda_reversion (venta_detalle_id, motivo, puede_revender) VALUES (?,?,?)');
    $ok = $stmt->execute([$det, $motivo, $rev?1:0]);
    if (!$ok) throw new Exception('No se pudo registrar');
    echo json_encode(['success'=>true]); return;
  }
  if ($action==='eliminar'){
    $id = (int)($_POST['id'] ?? 0);
    if ($id<=0) throw new Exception('ID inválido');
    // Verificar existencia
    $ex = $pdo->prepare('SELECT venta_detalle_id FROM tienda_reversion WHERE id=?');
    $ex->execute([$id]);
    $exists = $ex->fetchColumn();
    if ($exists===false) throw new Exception('Reversión no encontrada');
    // Eliminar
    $del = $pdo->prepare('DELETE FROM tienda_reversion WHERE id=?');
    $ok = $del->execute([$id]);
    if (!$ok) throw new Exception('No se pudo eliminar');
    echo json_encode(['success'=>true]); return;
  }
  echo json_encode(['success'=>false,'message'=>'Acción no soportada']);
}catch(Throwable $e){ http_response_code(400); echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }


