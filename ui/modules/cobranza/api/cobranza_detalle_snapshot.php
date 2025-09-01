<?php
header('Content-Type: application/json');
require_once '../../../controllers/AuthController.php';
require_once '../../../config/database.php';

try {
	$auth = new AuthController();
	$auth->requireModule('cobranza.comunicaciones');
	if ($_SERVER['REQUEST_METHOD'] !== 'GET') { throw new Exception('Método no permitido'); }
	$comId = isset($_GET['id']) ? (int)$_GET['id'] : (int)($_GET['comunicacion_id'] ?? 0);
	if ($comId <= 0) { throw new Exception('comunicacion_id inválido'); }

	$conn = getConnection();
	// Obtener detalle
	$stmt = $conn->prepare("SELECT d.*, u.nombre_completo AS creado_por_nombre
		FROM cobranza_detalle_mora d
		LEFT JOIN control_usuarios u ON u.id = d.creado_por
		WHERE d.comunicacion_id = ?
		LIMIT 1");
	$stmt->execute([$comId]);
	$detalle = $stmt->fetch(PDO::FETCH_ASSOC);
	if (!$detalle) {
		echo json_encode(['success'=>true,'data'=>['detalle'=>null,'creditos'=>[]]]);
		exit;
	}
	// Obtener créditos del detalle
	$stmtC = $conn->prepare("SELECT id, numero_credito, deuda_capital, deuda_mora, dias_mora, fecha_pago
		FROM cobranza_detalle_mora_credito
		WHERE detalle_id = ?
		ORDER BY numero_credito");
	$stmtC->execute([(int)$detalle['id']]);
	$creditos = $stmtC->fetchAll(PDO::FETCH_ASSOC);

	echo json_encode(['success'=>true,'data'=>['detalle'=>$detalle,'creditos'=>$creditos]]);
} catch (Throwable $e) {
	http_response_code(400);
	echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
