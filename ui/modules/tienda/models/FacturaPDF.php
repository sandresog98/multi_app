<?php
require_once __DIR__ . '/../../../vendor/tcpdf/tcpdf.php';

class FacturaPDF extends TCPDF {
    
    private $datosFactura;
    private $logoPath;
    
    public function __construct($datosFactura) {
        parent::__construct('P', PDF_UNIT, 'LETTER', true, 'UTF-8', false);
        
        $this->datosFactura = $datosFactura;
        $this->logoPath = __DIR__ . '/../../../assets/img/logo_telmovil.png';
        
        // Configuración del documento
        $this->SetCreator('Sistema Multi App');
        $this->SetAuthor('TELMOVIL');
        $this->SetTitle('Factura de Venta - ' . ($datosFactura['numero_factura'] ?? 'N/A'));
        $this->SetSubject('Factura de Venta');
        $this->SetKeywords('factura, venta, telmovil');
        
        // Configuración de márgenes para tamaño carta
        $this->SetMargins(15, 20, 15);
        $this->SetHeaderMargin(5);
        $this->SetFooterMargin(10);
        
        // Configuración de fuente
        $this->SetFont('helvetica', '', 10);
        
        // Auto page breaks
        $this->SetAutoPageBreak(TRUE, 25);
        
        // Deshabilitar salida automática
        $this->SetAutoPageBreak(TRUE, 25);
    }
    
    // Header personalizado
    public function Header() {
        // Logo TELMOVIL
        if (file_exists($this->logoPath)) {
            $this->Image($this->logoPath, 13, 15, 45, 15, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }
        
        // Información de la empresa
        $this->SetFont('helvetica', 'B', 12);
        $this->SetY(15);
        $this->SetX(60);
        $this->Cell(200, 6, 'TELMOVIL', 0, 1, 'L');
        
        $this->SetFont('helvetica', '', 9);
        $this->SetX(60);
        $this->Cell(200, 4, 'NIT 1020820109-6', 0, 1, 'L');
        
        $this->SetX(60);
        $this->Cell(200, 4, 'PAC GALERIAS, Bogotá, D.C.', 0, 1, 'L');
        
        $this->SetX(60);
        $this->Cell(200, 4, 'Tel: +573102738833', 0, 1, 'L');
        
        $this->SetX(60);
        $this->Cell(200, 4, 'atenciongalerias@coomultiunion.com', 0, 1, 'L');
        
        // Información de la factura
        $this->SetFont('helvetica', 8);
        $this->SetY(15);
        $this->SetX(80);
        $this->Cell(120, 4, 'Factura de venta', 0, 1, 'R');
        
        $this->SetFont('helvetica', 'B', 10);
        $this->SetX(80);
        $this->Cell(120, 6, 'No. ' . ($this->datosFactura['numero_factura'] ?? 'N/A'), 0, 1, 'R');
        
        $this->SetFont('helvetica', '', 8);
        $this->SetX(80);
        $this->Cell(120, 4, 'No responsable de IVA', 0, 1, 'R');
        
        $this->SetX(80);
        $this->Cell(120, 4, 'Factura de venta original', 0, 1, 'R');

        // Fechas
        // $this->SetX(80);
        // $this->Cell(120, 4, 'Fecha: ' . date('d/m/Y', strtotime($this->datosFactura['fecha_venta'] ?? date('Y-m-d'))), 0, 1, 'R');
        
        // $this->SetX(80);
        // $this->Cell(120, 4, 'Vencimiento: ' . date('d/m/Y', strtotime($this->datosFactura['fecha_venta'] ?? date('Y-m-d'))), 0, 1, 'R');
        
        // Línea separadora (ajustada para tamaño carta)
        $this->SetY(45);
        $this->Line(15, 45, 201, 45);
    }
    
    // Footer personalizado
    public function Footer() {
        $this->SetY(-50);
        $this->SetFont('helvetica', '', 8);
        
        // QR Code (placeholder)
        // $this->SetY(-50);
        // $this->SetX(15);
        // $this->Cell(70, 70, '', 1, 0, 'C');
        
        // Información fiscal
        $this->SetY(-60);
        $this->SetFont('helvetica', '', 8);
        $this->Cell(200, 4, 'Moneda: COP', 0, 1, 'L');
        $this->Cell(200, 4, 'Fecha y hora de expedición: ' . date('Y-m-d\TH:i:s'), 0, 1, 'L');
        $this->Cell(200, 4, 'Tipo de factura: Factura de venta', 0, 1, 'L');
        $this->Cell(200, 4, 'Forma de pago: Contado', 0, 1, 'L');
        $this->Cell(200, 4, 'Medio de pago: Efectivo', 0, 1, 'L');

        // Espacio entre información fiscal y líneas de firma
        $this->SetY($this->GetY() + 20);
        
        // Líneas de firma separadas
        $this->Line(15, $this->GetY(), 70, $this->GetY()); // Línea sobre "ELABORADO POR"
        $this->Line(90, $this->GetY(), 170, $this->GetY()); // Línea sobre "ACEPTADA"
        
        // Texto de las firmas
        $this->Cell(90, 4, 'ELABORADO POR', 0, 0, 'L');
        $this->SetX(90);
        $this->Cell(90, 4, 'ACEPTADA, FIRMA Y/O SELLO Y FECHA', 0, 1, 'L');
    }
    
    // Generar la factura completa
    public function generarFactura() {
        $this->AddPage();
        
        // Información del cliente
        $this->SetFont('helvetica', 'B', 8);
        $this->SetY(55);
        $this->Cell(0, 4, 'SEÑOR(ES)', 0, 1, 'L');
        
        $this->SetFont('helvetica', '', 8);
        $this->Cell(0, 4, $this->datosFactura['cliente_nombre'] ?? 'Cliente General', 0, 1, 'L');

        $this->SetY(65);
        $this->SetFont('helvetica', 'B', 8);
        $this->Cell(50, 4, 'CC / NIT', 0, 1, 'L');
        
        $this->SetFont('helvetica', '', 8);
        $this->Cell(50, 4, $this->datosFactura['cliente_documento'] ?? 'No especificado', 0, 1, 'L');
        
        $this->SetY(55);
        $this->SetX(120);
        $this->SetFont('helvetica', 'B', 8);
        $this->Cell(100, 4, 'TELÉFONO', 0, 1, 'L');
        
        $this->SetFont('helvetica', '', 8);
        $this->SetX(120);
        $this->Cell(100, 4, $this->datosFactura['cliente_telefono'] ?? 'No especificado', 0, 1, 'L');
        
        $this->SetY(65);
        $this->SetX(120);
        $this->SetFont('helvetica', 'B', 8);
        $this->Cell(100, 4, 'EMAIL', 0, 1, 'L');
        
        $this->SetFont('helvetica', '', 8);
        $this->SetX(120);
        $direccion = $this->datosFactura['cliente_direccion'] ?? 'No especificada';
        if (strlen($direccion) > 40) {
            $direccion = substr($direccion, 0, 37) . '...';
        }
        $this->Cell(100, 4, $direccion, 0, 1, 'L');
        
        // Tabla de productos
        $this->SetY(80);
        $this->generarTablaProductos();
        
        // Totales
        $this->generarTotales();
    }
    
    private function generarTablaProductos() {
        // Encabezados de la tabla (más compactos)
        $this->SetFont('helvetica', 'B', 9);
        $this->SetFillColor(240, 240, 240);
        
        $this->Cell(70, 6, 'Producto', 1, 0, 'C', true);
        $this->Cell(30, 6, 'Cant.', 1, 0, 'C', true);
        $this->Cell(40, 6, 'Valor Und.', 1, 0, 'C', true);
        $this->Cell(40, 6, 'Total', 1, 1, 'C', true);
        
        // Datos de productos
        $this->SetFont('helvetica', '', 9);
        $y = $this->GetY();
        
        $productos = $this->datosFactura['productos'] ?? [];
        foreach ($productos as $index => $producto) {
            $this->SetY($y + ($index * 18));
            
            // Producto con IMEI si existe
            $nombre = $producto['nombre'] ?? 'Producto';
            if (strlen($nombre) > 40) {
                $nombre = substr($nombre, 0, 40) . '...';
            }
            
            // Agregar IMEI al texto del producto si existe
            $textoProducto = $nombre;
            if (!empty($producto['imei'])) {
                $textoProducto .= "\nIMEI: " . $producto['imei'];
            }
            
            $this->Cell(70, 9, $textoProducto, 1, 0, 'L');

            // Cantidad
            $cantidad = $producto['cantidad'] ?? 1;
            $this->Cell(30, 9, $cantidad, 1, 0, 'C');
            
            // Valor Unitario
            $precio = $producto['precio'] ?? 0;
            $this->Cell(40, 9, '$' . number_format($precio, 0, ',', '.'), 1, 0, 'R');
            
            // Total
            $total = $precio * $cantidad;
            $this->Cell(40, 9, '$' . number_format($total, 0, ',', '.'), 1, 1, 'R');
        
        }
    }
    
    private function generarTotales() {
        $this->SetY($this->GetY() + 3);
        
        // Subtotal
        // $this->SetFont('helvetica', '', 8);
        // $this->SetX(115);
        // $this->Cell(40, 6, 'Subtotal', 0, 0, 'L');
        // s$subtotal = $this->datosFactura['subtotal'] ?? 0;
        // $this->Cell(40, 6, '$' . number_format($subtotal, 0, ',', '.'), 0, 1, 'R');
        
        // Total
        $this->SetFont('helvetica', 'B', 9);
        $this->SetFillColor(170, 170, 170);
        $this->SetX(115);
        $this->Cell(40, 9, 'Total', 1, 0, 'L', true);
        $total = $this->datosFactura['total'] ?? $subtotal;
        $this->Cell(40, 9, '$' . number_format($total, 0, ',', '.'), 1, 1, 'R', true);
    }
}
?>
