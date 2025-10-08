<?php
require_once '../../../controllers/AuthController.php';
require_once '../models/FacturaPDF.php';
require_once '../models/TiendaCatalogo.php';

try {
    $auth = new AuthController();
    $auth->requireModule('tienda.ventas');
    
    $ventaId = $_GET['id'] ?? '';
    if (!$ventaId) {
        throw new Exception('ID de venta requerido');
    }
    
    // Obtener datos de la venta
    $catalogoModel = new TiendaCatalogo();
    $venta = $catalogoModel->obtenerVentaCompleta($ventaId);
    
    if (!$venta) {
        throw new Exception('Venta no encontrada');
    }
    
    // Preparar datos para el PDF
    $datosFactura = [
        'numero_factura' => $venta['numero_factura'] ?? 'FAC-' . str_pad($ventaId, 6, '0', STR_PAD_LEFT),
        'fecha_venta' => $venta['fecha_venta'] ?? date('Y-m-d'),
        'cliente_nombre' => $venta['cliente_nombre'] ?? 'Cliente General',
        'cliente_direccion' => $venta['cliente_direccion'] ?? '',
        'cliente_telefono' => $venta['cliente_telefono'] ?? '',
        'cliente_documento' => $venta['cliente_documento'] ?? '',
        'productos' => $venta['productos'] ?? [],
        'subtotal' => $venta['subtotal'] ?? 0,
        'total' => $venta['total'] ?? 0
    ];
    
    // Generar PDF
    $pdf = new FacturaPDF($datosFactura);
    $pdf->generarFactura();
    
    // Configurar headers para descarga
    $filename = 'Factura_' . $datosFactura['numero_factura'] . '_' . date('Y-m-d') . '.pdf';
    
    // Limpiar cualquier output anterior
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Configurar headers
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    // Output del PDF directamente
    $pdf->Output($filename, 'D');
    
} catch (Exception $e) {
    // Limpiar cualquier output anterior
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
