<?php
// Deshabilitar errores en producción para que no interfieran con JSON
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/paths.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Verificar autenticación - iniciar sesión si no está iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_name('multiapptwo_cx');
        session_start();
    }
    
    $cedula = $_SESSION['cx_cedula'] ?? '';
    if (empty($cedula)) {
        echo json_encode(['success' => false, 'message' => 'No autenticado']);
        exit;
    }
    
    $conn = getConnection();
    
    // Paginación
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $page = max(1, $page);
    $limit = max(1, min(100, $limit));
    $offset = ($page - 1) * $limit;
    
    // Obtener transacciones del asociado
    $sql = "SELECT t.id, 
                   t.origen_pago, 
                   t.pse_id, 
                   t.confiar_id, 
                   t.recibo_caja_sifone AS id_sifone,
                   t.valor_pago_total AS valor_pago,
                   COALESCE(SUM(d.valor_asignado), 0) AS total_asignado,
                   t.fecha_creacion AS fecha,
                   COALESCE(DATE(p.fecha_hora_resolucion_de_la_transaccion), b.fecha) AS ref_fecha
            FROM control_transaccion t
            LEFT JOIN control_transaccion_detalle d ON d.transaccion_id = t.id
            LEFT JOIN banco_pse p ON p.pse_id = t.pse_id
            LEFT JOIN banco_confiar b ON b.confiar_id = t.confiar_id
            WHERE t.cedula = ?
            GROUP BY t.id
            ORDER BY t.fecha_creacion DESC
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$cedula, $limit, $offset]);
    $transacciones = $stmt->fetchAll();
    
    // Contar total
    $countSql = "SELECT COUNT(*) AS total FROM control_transaccion WHERE cedula = ?";
    $countStmt = $conn->prepare($countSql);
    $countStmt->execute([$cedula]);
    $total = (int)($countStmt->fetch()['total'] ?? 0);
    
    // Para cada transacción, obtener los detalles (items)
    foreach ($transacciones as &$tx) {
        $itemsSql = "SELECT tipo_rubro, referencia_credito, producto_id, descripcion, valor_asignado
                     FROM control_transaccion_detalle 
                     WHERE transaccion_id = ? 
                     ORDER BY id";
        $itemsStmt = $conn->prepare($itemsSql);
        $itemsStmt->execute([$tx['id']]);
        $items = $itemsStmt->fetchAll();
        
        // Formatear items según especificación
        $tx['items'] = array_map(function($item) {
            $descripcion = '';
            if ($item['tipo_rubro'] === 'credito') {
                $descripcion = 'credito ' . ($item['referencia_credito'] ?? '');
            } elseif ($item['tipo_rubro'] === 'producto') {
                $descripcion = $item['descripcion'] ?? '';
            } else {
                $descripcion = $item['descripcion'] ?? '';
            }
            
            return [
                'descripcion' => $descripcion,
                'valor' => (float)($item['valor_asignado'] ?? 0)
            ];
        }, $items);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $transacciones,
        'meta' => [
            'total' => $total,
            'pages' => $limit > 0 ? (int)ceil($total / $limit) : 1,
            'current_page' => $page,
            'limit' => $limit
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error del servidor: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    exit;
} catch (Error $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fatal: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    exit;
}
?>

