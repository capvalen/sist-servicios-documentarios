<?php
require_once __DIR__ . '/vendor/autoload.php';

// Crear un nuevo objeto PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetFont('helvetica', '', 9); 

// Establecer el título del documento
$pdf->SetTitle('Mi primer PDF con TCPDF');

// Agregar una página
$pdf->AddPage();

// Escribir algo en el PDF
$pdf->Cell(0, 10, '¡Hola, mundo! Este es mi primer PDF creado con TCPDF.', 0, 1, 'C', 0, '', 0, false, 'T', 'M');

// Salida del PDF
$pdf->Output('example.pdf', 'I');