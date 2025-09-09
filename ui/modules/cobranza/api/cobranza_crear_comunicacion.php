<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../models/Logger.php';
require_once '../models/Comunicacion.php';
require_once '../models/Cobranza.php';
require_once '../../../config/database.php';

header('Content-Type: application/json');

try {
	$auth = new AuthController();
	$auth->requireModule('cobranza.comunicaciones');
	$user = $auth->getCurrentUser();

	if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Método no permitido');
	$cedula = trim($_POST['cedula'] ?? '');
	$tipo = trim($_POST['tipo'] ?? '');
	$estado = trim($_POST['estado'] ?? '');
	$comentario = trim($_POST['comentario'] ?? '');
	$fecha = trim($_POST['fecha'] ?? '');
	if ($estado === 'Sin comunicación') { $estado = 'Sin respuesta'; }
	$allowedEstados = ['Informa de pago realizado', 'Comprometido a realizar el pago', 'Sin respuesta'];
	if (!$cedula || !$tipo || !$estado || !$fecha) throw new Exception('Datos incompletos');
	if (!in_array($estado, $allowedEstados, true)) throw new Exception('Estado inválido');

	$model = new Comunicacion();
	$id = $model->crear($cedula, $tipo, $estado, $comentario, $fecha, (int)($user['id'] ?? null));

	// Snapshot de detalle de mora (best-effort)
	try {
		$db = getConnection();
		$db->beginTransaction();
		$co = new Cobranza();
		$detalle = $co->obtenerDetalleCompletoAsociado($cedula);
		$aportesMonto = (float)($detalle['asociado']['aporte'] ?? 0);
		$creditos = $detalle['creditos'] ?? [];
		$stmtDM = $db->prepare("INSERT INTO cobranza_detalle_mora (comunicacion_id, asociado_cedula, aportes_monto, total_creditos, creado_por) VALUES (?, ?, ?, ?, ?)");
		$stmtDM->execute([$id, $cedula, $aportesMonto, count($creditos), (int)($user['id'] ?? null)]);
		$detalleId = (int)$db->lastInsertId();
		if (!empty($creditos)){
			$insC = $db->prepare("INSERT INTO cobranza_detalle_mora_credito (detalle_id, numero_credito, deuda_capital, deuda_mora, dias_mora, fecha_pago) VALUES (?, ?, ?, ?, ?, ?)");
			foreach ($creditos as $cr){
				$insC->execute([
					$detalleId,
					(string)($cr['numero_credito'] ?? ''),
					(float)($cr['deuda_capital'] ?? 0),
					(float)($cr['saldo_mora'] ?? 0),
					(int)($cr['dias_mora'] ?? 0),
					($cr['fecha_pago'] ?? null)
				]);
			}
		}
		$db->commit();
	} catch (Throwable $se) {
		try { $db->rollBack(); } catch (Throwable $ignored) {}
		(new Logger())->logEditar('cobranza', 'Snapshot detalle mora falló', null, ['error'=>$se->getMessage(), 'cedula'=>$cedula, 'comunicacion_id'=>$id]);
	}

	// Log creación
	(new Logger())->logCrear('cobranza', 'Registrar comunicación', [
		'id' => $id,
		'cedula' => $cedula,
		'tipo' => $tipo,
		'estado' => $estado
	]);

	echo json_encode(['success'=>true,'id'=>$id]);
} catch (Throwable $e) {
	try { (new Logger())->logEditar('cobranza', 'Error al registrar comunicación', null, ['error'=>$e->getMessage()]); } catch (Throwable $ignored) {}
	http_response_code(400);
	echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}


