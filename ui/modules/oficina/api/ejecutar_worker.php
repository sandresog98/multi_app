<?php
require_once '../../../controllers/AuthController.php';
require_once '../models/Cargas.php';

header('Content-Type: application/json');

try {
    $auth = new AuthController();
    $auth->requireModule('oficina.cargas');
    
    // Verificar que sea una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    // Comando a ejecutar
    $comando = 'python3.10 /var/www/html/multi_app/py/worker.py --run-once';
    
    // Ejecutar el comando
    $output = [];
    $returnCode = 0;
    
    // Ejecutar el comando y capturar la salida
    exec($comando . ' 2>&1', $output, $returnCode);
    
    // Verificar si el comando se ejecutó correctamente
    if ($returnCode === 0) {
        $mensaje = 'Worker ejecutado exitosamente';
        if (!empty($output)) {
            $mensaje .= '. Salida: ' . implode(' ', $output);
        }
        
        echo json_encode([
            'success' => true,
            'message' => $mensaje,
            'output' => $output,
            'return_code' => $returnCode
        ]);
    } else {
        $errorMsg = 'Error al ejecutar worker (código: ' . $returnCode . ')';
        if (!empty($output)) {
            $errorMsg .= '. Error: ' . implode(' ', $output);
        }
        
        echo json_encode([
            'success' => false,
            'message' => $errorMsg,
            'output' => $output,
            'return_code' => $returnCode
        ]);
    }
    
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
