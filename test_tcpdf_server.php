<?php
// Script de prueba para verificar TCPDF en el servidor
require_once 'ui/vendor/tcpdf/tcpdf.php';

try {
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator('Sistema Multi App');
    $pdf->SetAuthor('TELMOVIL');
    $pdf->SetTitle('Prueba Servidor');
    
    $pdf->SetMargins(15, 20, 15);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetAutoPageBreak(TRUE, 25);
    
    $pdf->AddPage();
    $pdf->Cell(0, 10, 'TCPDF funciona correctamente en el servidor', 0, 1, 'C');
    $pdf->Cell(0, 10, 'Fecha: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
    
    $pdf->Output('test_servidor.pdf', 'D');
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
