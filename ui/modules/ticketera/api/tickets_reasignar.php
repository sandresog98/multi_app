<?php
require_once '../../../controllers/AuthController.php';
require_once '../controllers/TicketeraController.php';

header('Content-Type: application/json');

try {
  $auth = new AuthController();
  $auth->requireModule('ticketera');
  $controller = new TicketeraController();

  $input = json_decode(file_get_contents('php://input'), true);
  if (!is_array($input)) { $input = $_POST; }
  $res = $controller->tickets_reasignar($input);
  echo json_encode($res);
} catch (Throwable $e) {
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
?>


