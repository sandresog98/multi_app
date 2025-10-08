<?php
require_once '../../../controllers/AuthController.php';
require_once '../models/FacturaPDF.php';
require_once '../models/TiendaCatalogo.php';

// ui/modules/tienda/api/vista_previa_pdf.php?id=2

try {
    $auth = new AuthController();
    $auth->requireModule('tienda.ventas');
    
    $ventaId = $_GET['id'] ?? '';
    if (!$ventaId) {
        throw new Exception('ID de venta requerido');
    }
    
    $catalogoModel = new TiendaCatalogo();
    $venta = $catalogoModel->obtenerVentaCompleta($ventaId);
    
    if (!$venta) {
        throw new Exception('Venta no encontrada');
    }
    
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
    
    $pdf = new FacturaPDF($datosFactura);
    $pdf->generarFactura();
    
    // Limpiar buffer de salida
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Headers para vista previa (no descarga)
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="vista_previa_factura_' . $ventaId . '.pdf"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    $pdf->Output('vista_previa_factura_' . $ventaId . '.pdf', 'I');
    
} catch (Exception $e) {
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    http_response_code(400);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Error - Vista Previa PDF</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 50px; background: #f8f9fa; }
            .error { background: #f8d7da; color: #721c24; padding: 20px; border-radius: 5px; border: 1px solid #f5c6cb; }
            .btn { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px; }
        </style>
    </head>
    <body>
        <div class="error">
            <h3>Error al generar vista previa</h3>
            <p>' . htmlspecialchars($e->getMessage()) . '</p>
            <a href="javascript:history.back()" class="btn">Volver</a>
        </div>
    </body>
    </html>';
}
?>
