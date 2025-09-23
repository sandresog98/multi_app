<?php
require_once '../../../controllers/AuthController.php';
require_once '../models/TiendaClientes.php';

header('Content-Type: application/json');
try{
  $auth = new AuthController();
  $auth->requireModule('tienda.clientes');
  $model = new TiendaClientes();
  $action = $_GET['action'] ?? ($_POST['action'] ?? '');
  if ($action==='listar') {
    $pdo = getConnection();
    $rows = $model->listar();
    // Marcar si el cliente fue usado en ventas
    foreach ($rows as &$r){
      $q = $pdo->prepare('SELECT COUNT(*) FROM tienda_venta WHERE cliente_id = ?');
      $q->execute([(int)($r['id'] ?? 0)]);
      $r['usado'] = (int)$q->fetchColumn();
    }
    echo json_encode(['success'=>true,'items'=>$rows]); return; }
  if ($action==='guardar') {
    $id = isset($_POST['id'])&&$_POST['id']!==''?(int)$_POST['id']:null;
    $nombre = trim($_POST['nombre'] ?? '');
    $doc = trim($_POST['nit_cedula'] ?? '');
    $tel = trim($_POST['telefono'] ?? '');
    $mail = trim($_POST['email'] ?? '');
    if ($nombre===''||$doc==='') throw new Exception('Nombre y documento requeridos');
    echo json_encode($model->guardar($id,$nombre,$doc,$tel,$mail)); return;
  }
  if ($action==='eliminar') {
    $id = (int)($_POST['id'] ?? 0); if ($id<=0) throw new Exception('ID inválido');
    // Bloqueo: si el cliente ya fue usado en ventas, no se puede eliminar
    $pdo = getConnection();
    $q = $pdo->prepare('SELECT COUNT(*) FROM tienda_venta WHERE cliente_id = ?');
    $q->execute([$id]);
    if ((int)$q->fetchColumn() > 0) throw new Exception('No se puede eliminar: el cliente tiene ventas asociadas');
    echo json_encode($model->eliminar($id)); return;
  }
  echo json_encode(['success'=>false,'message'=>'Acción no soportada']);
}catch(Throwable $e){ http_response_code(400); echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }


