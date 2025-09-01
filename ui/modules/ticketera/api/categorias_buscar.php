<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../controllers/AuthController.php';
require_once __DIR__ . '/../../../config/database.php';

try{
    $auth = new AuthController();
    $auth->requireModule('ticketera');
    $q = trim($_GET['q'] ?? '');
    $limit = (int)($_GET['limit'] ?? 10);
    if ($limit <= 0 || $limit > 50) $limit = 10;
    $pdo = getConnection();
    if ($q === '') {
        $stmt = $pdo->prepare("SELECT id, nombre FROM ticketera_categoria WHERE estado_activo = 1 ORDER BY nombre LIMIT ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("SELECT id, nombre FROM ticketera_categoria WHERE estado_activo = 1 AND nombre LIKE ? ORDER BY nombre LIMIT ?");
        $stmt->bindValue(1, '%'.$q.'%');
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
    }
    echo json_encode(['success'=>true,'items'=>$stmt->fetchAll()]);
}catch(Throwable $e){
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}


