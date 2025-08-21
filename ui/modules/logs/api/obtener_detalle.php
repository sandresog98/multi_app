<?php
header('Content-Type: application/json; charset=utf-8');

require_once '../../../controllers/AuthController.php';
require_once '../../../config/database.php';
require_once '../../../models/Logger.php';

try {
    $auth = new AuthController();
    $auth->requireRole('admin');

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        exit;
    }

    $logger = new Logger();
    $log = $logger->getLogById($id);
    if (!$log) {
        echo json_encode(['success' => false, 'message' => 'Log no encontrado']);
        exit;
    }

    $datos_anteriores = [];
    $datos_nuevos = [];
    if (!empty($log['datos_anteriores'])) {
        $decoded = json_decode($log['datos_anteriores'], true);
        if (is_array($decoded)) { $datos_anteriores = $decoded; }
    }
    if (!empty($log['datos_nuevos'])) {
        $decoded = json_decode($log['datos_nuevos'], true);
        if (is_array($decoded)) { $datos_nuevos = $decoded; }
    }

    $nivel_color = 'info';
    if ($log['nivel'] === 'error') $nivel_color = 'danger';
    elseif ($log['nivel'] === 'warning') $nivel_color = 'warning';
    elseif ($log['nivel'] === 'critical') $nivel_color = 'dark';

    echo json_encode([
        'success' => true,
        'data' => [
            'id' => (int)$log['id'],
            'usuario' => $log['usuario'] ?? null,
            'nombre_completo' => $log['nombre_completo'] ?? null,
            'modulo' => $log['modulo'],
            'accion' => $log['accion'],
            'nivel' => $log['nivel'],
            'nivel_color' => $nivel_color,
            'timestamp' => $log['timestamp'],
            'timestamp_formateado' => date('d/m/Y H:i:s', strtotime($log['timestamp'])),
            'ip_address' => $log['ip_address'],
            'user_agent' => $log['user_agent'],
            'detalle' => $log['detalle'],
            'datos_anteriores_formateados' => $datos_anteriores,
            'datos_nuevos_formateados' => $datos_nuevos
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}


