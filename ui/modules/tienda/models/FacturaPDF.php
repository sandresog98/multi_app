<?php
require_once __DIR__ . '/../../../vendor/tcpdf/tcpdf.php';

class FacturaPDF extends TCPDF {
    
    private $datosFactura;
    private $logoPath;
    
    public function __construct($datosFactura) {
        parent::__construct(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        $this->datosFactura = $datosFactura;
        $this->logoPath = __DIR__ . '/../../../assets/img/logo_telmovil.png';
        
        // Configuración del documento
        $this->SetCreator('Sistema Multi App');
        $this->SetAuthor('TELMOVIL');
        $this->SetTitle('Factura de Venta - ' . ($datosFactura['numero_factura'] ?? 'N/A'));
        $this->SetSubject('Factura de Venta');
        $this->SetKeywords('factura, venta, telmovil');
        
        // Configuración de márgenes (más espacio a la derecha)
        $this->SetMargins(15, 20, 20);
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
            $this->Image($this->logoPath, 15, 10, 40, 15, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }
        
        // Información de la empresa
        $this->SetFont('helvetica', 'B', 12);
        $this->SetY(15);
        $this->SetX(60);
        $this->Cell(200, 6, 'TELMOVIL', 0, 1, 'L');
        
        $this->SetFont('helvetica', '', 9);
        $this->SetX(60);
        $this->Cell(200, 4, 'PAMEL ANDREA VIDAL CAGUA - NIT 1020820109-6', 0, 1, 'L');
        
        $this->SetX(60);
        $this->Cell(200, 4, 'PAC GALERIAS, Bogotá, D.C.', 0, 1, 'L');
        
        $this->SetX(60);
        $this->Cell(200, 4, 'Tel: +573102738833', 0, 1, 'L');
        
        $this->SetX(60);
        $this->Cell(200, 4, 'atenciongalerias@coomultiunion.com', 0, 1, 'L');
        
        // Información de la factura
        $this->SetFont('helvetica', 'B', 10);
        $this->SetY(15);
        $this->SetX(400);
        $this->Cell(120, 6, 'No. ' . ($this->datosFactura['numero_factura'] ?? 'N/A'), 0, 1, 'R');
        
        $this->SetFont('helvetica', '', 8);
        $this->SetX(400);
        $this->Cell(120, 4, 'Factura de venta', 0, 1, 'R');
        
        $this->SetX(400);
        $this->Cell(120, 4, 'Factura de venta original', 0, 1, 'R');
        
        // Fechas
        $this->SetX(400);
        $this->Cell(120, 4, 'Fecha: ' . date('d/m/Y', strtotime($this->datosFactura['fecha_venta'] ?? date('Y-m-d'))), 0, 1, 'R');
        
        $this->SetX(400);
        $this->Cell(120, 4, 'Vencimiento: ' . date('d/m/Y', strtotime($this->datosFactura['fecha_venta'] ?? date('Y-m-d'))), 0, 1, 'R');
        
        // Línea separadora
        $this->SetY(45);
        $this->Line(15, 45, 190, 45);
    }
    
    // Footer personalizado
    public function Footer() {
        $this->SetY(-25);
        $this->SetFont('helvetica', '', 8);
        
        // Línea de firma
        $this->Line(15, $this->GetY() + 5, 200, $this->GetY() + 5);
        $this->Line(220, $this->GetY() + 5, 380, $this->GetY() + 5);
        
        $this->SetY($this->GetY() + 8);
        $this->Cell(185, 4, 'ELABORADO POR', 0, 0, 'L');
        $this->Cell(160, 4, 'ACEPTADA, FIRMA Y/O SELLO Y FECHA', 0, 1, 'L');
        
        // QR Code (placeholder)
        $this->SetY(-15);
        $this->SetX(15);
        $this->Cell(70, 70, '', 1, 0, 'C');
        
        // Información fiscal
        $this->SetY(-12);
        $this->SetX(100);
        $this->SetFont('helvetica', '', 8);
        $this->Cell(200, 4, 'Moneda: COP', 0, 1, 'L');
        $this->Cell(200, 4, 'Fecha y hora de expedición: ' . date('Y-m-d\TH:i:s'), 0, 1, 'L');
        $this->Cell(200, 4, 'Tipo de factura: Factura de venta', 0, 1, 'L');
        $this->Cell(200, 4, 'Forma de pago: Contado', 0, 1, 'L');
        $this->Cell(200, 4, 'Medio de pago: Efectivo', 0, 1, 'L');
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
        $this->Cell(100, 4, 'DIRECCIÓN', 0, 1, 'L');
        
        $this->SetFont('helvetica', '', 8);
        $direccion = $this->datosFactura['cliente_direccion'] ?? 'No especificada';
        if (strlen($direccion) > 40) {
            $direccion = substr($direccion, 0, 37) . '...';
        }
        $this->Cell(100, 4, $direccion, 0, 1, 'L');
        
        $this->SetY(75);
        $this->SetFont('helvetica', 'B', 8);
        $this->Cell(100, 4, 'TELÉFONO', 0, 0, 'L');
        
        $this->SetFont('helvetica', '', 8);
        $this->Cell(100, 4, $this->datosFactura['cliente_telefono'] ?? 'No especificado', 0, 1, 'L');
        
        $this->SetY(75);
        $this->SetX(200);
        $this->SetFont('helvetica', 'B', 8);
        $this->Cell(50, 4, 'CC', 0, 1, 'L');
        
        $this->SetFont('helvetica', '', 8);
        $this->SetX(200);
        $this->Cell(50, 4, $this->datosFactura['cliente_documento'] ?? 'No especificado', 0, 1, 'L');
        
        // Tabla de productos
        $this->SetY(90);
        $this->generarTablaProductos();
        
        // Totales
        $this->generarTotales();
    }
    
    private function generarTablaProductos() {
        // Encabezados de la tabla (más compactos)
        $this->SetFont('helvetica', 'B', 7);
        $this->SetFillColor(240, 240, 240);
        
        $this->Cell(60, 8, 'Ítem', 1, 0, 'C', true);
        $this->Cell(30, 8, 'Precio', 1, 0, 'C', true);
        $this->Cell(25, 8, 'Cant.', 1, 0, 'C', true);
        $this->Cell(30, 8, 'Desc.', 1, 0, 'C', true);
        $this->Cell(35, 8, 'Total', 1, 1, 'C', true);
        
        // Datos de productos
        $this->SetFont('helvetica', '', 7);
        $y = $this->GetY();
        
        $productos = $this->datosFactura['productos'] ?? [];
        foreach ($productos as $index => $producto) {
            $this->SetY($y + ($index * 18));
            
            // Ítem (truncar si es muy largo)
            $nombre = $producto['nombre'] ?? 'Producto';
            if (strlen($nombre) > 20) {
                $nombre = substr($nombre, 0, 17) . '...';
            }
            $this->Cell(60, 18, $nombre, 1, 0, 'L');
            
            // Precio
            $precio = $producto['precio'] ?? 0;
            $this->Cell(30, 18, '$' . number_format($precio, 0, ',', '.'), 1, 0, 'R');
            
            // Cantidad
            $cantidad = $producto['cantidad'] ?? 1;
            $this->Cell(25, 18, $cantidad, 1, 0, 'C');
            
            // Descuento
            $this->Cell(30, 18, '$0', 1, 0, 'R');
            
            // Total
            $total = $precio * $cantidad;
            $this->Cell(35, 18, '$' . number_format($total, 0, ',', '.'), 1, 1, 'R');
            
            // IMEI si existe
            if (!empty($producto['imei'])) {
                $this->SetY($this->GetY() - 13);
                $this->SetX(65);
                $this->SetFont('helvetica', '', 6);
                $this->Cell(0, 3, '(IMEI: ' . $producto['imei'] . ')', 0, 1, 'L');
                $this->SetFont('helvetica', '', 7);
            }
        }
    }
    
    private function generarTotales() {
        $this->SetY($this->GetY() + 10);
        
        // Subtotal
        $this->SetFont('helvetica', '', 8);
        $this->SetX(100);
        $this->Cell(40, 6, 'Subtotal', 0, 0, 'L');
        $subtotal = $this->datosFactura['subtotal'] ?? 0;
        $this->Cell(40, 6, '$' . number_format($subtotal, 0, ',', '.'), 0, 1, 'R');
        
        // Total
        $this->SetFont('helvetica', 'B', 8);
        $this->SetFillColor(170, 170, 170);
        $this->SetX(100);
        $this->Cell(40, 12, 'Total', 1, 0, 'L', true);
        $total = $this->datosFactura['total'] ?? $subtotal;
        $this->Cell(40, 12, '$' . number_format($total, 0, ',', '.'), 1, 1, 'R', true);
    }
}
?>
