<?php
error_reporting(E_ALL); ini_set("display_errors", 1);
require_once __DIR__ . '/vendor/autoload.php';
include 'api/conectInfocat.php';

$idOrden = base64_decode($_GET['idOrden']);

$sqlOrden = $db->prepare("SELECT * FROM orden_cabecera
where id = ?;");
$sqlOrden->execute([ $idOrden ]);
$rowOrden = $sqlOrden->fetch(PDO::FETCH_ASSOC);

$sqlProveedor = $db->prepare("SELECT p.* 
from proveedor p inner join orden_cabecera oc
on oc.idProveedor = p.id
where oc.id = ?;");
$sqlProveedor->execute([ $idOrden ]);
$rowProveedor = $sqlProveedor->fetch(PDO::FETCH_ASSOC);

$productos = [];
$sqlDetalle = $db->prepare("SELECT * FROM orden_detalle
where idOrden = ? and activo = 1;");
$sqlDetalle->execute([ $idOrden ]);
while($rowlDetalle = $sqlDetalle->fetch(PDO::FETCH_ASSOC))
	$productos[] = $rowlDetalle;

function fechaLatam($fechita){
	if($fechita){
		$fecha_latam = date("d/m/Y", strtotime($fechita));
		return $fecha_latam;
	}else return '';
}
function deuda($dias){
	if($dias==0) return 'Al contado';
	else if($dias>0) return 'Pago ' .$dias . ' día'. ($dias>1 ? 's':'') .' calendario';
	else if( $dias=='') return '-';
	else return '-';
}

// Crear un nuevo objeto PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetPrintHeader(false);
$pdf->SetPrintFooter(false);

// tamaño de fuente del documento
$pdf->SetFont('helvetica', '', 9);

$pdf->SetTitle('Órden de servicio');

$pdf->AddPage();
$pdf->Image('img/logo.jpeg', 0, 0, 60, 0, 'JPG', 'https://grupocimalperu.com/', 'L', true, 150);

$pdf->SetXY(100, 5);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->MultiCell(50, 2, 'ORDEN DE COMPRA: N° '.$rowOrden['orden'], 0, 'C', 0, 1, '', '', true);

$pdf->SetXY(150, 5);
$code = '1234567890'; // El código de barras que deseas generar
$style = array(
   'position' => 'R',
    'align' => 'C',
    'stretch' => false,
    'fitwidth' => true,
    'cellfitalign' => '',
    'border' => false,
    'hpadding' => 'auto',
    'vpadding' => 'auto',
    'fgcolor' => array(0,0,0),
    'bgcolor' => false, //array(255,255,255),
    'text' => false,
    'font' => 'helvetica',
    'fontsize' => 0,
    'stretchtext' => 4
);
$pdf->write1DBarcode($code, 'EAN13', '', '', '', 10, 0.4, $style, 'N');

$pdf->SetXY(5, 17);
$pdf->Cell(100, 0, $rowOrden['razon'], 0, 1, 'L', 0, '', 0);
$pdf->SetFont('helvetica', 'B', 8.5);
$pdf->SetXY(5, 22.3);
$pdf->MultiCell(40, 0, 'Factura a/Invoice to:'
."\nRuc"
."\nDirección / Address"
."\nTeléfono"
."\nCiudad / City, ST Zip"
."\nPaís  / Country"
."\nCasilla DTE:"
, 0, '', 0, 1, '', '', true);

$pdf->SetXY(45, 22.3);
$pdf->SetFont('helvetica', '', 8.5);
$pdf->MultiCell(45, 0, ''
."\n: ". $rowOrden['ruc']
."\n: ". $rowOrden['direccion']
."\n: ". $rowOrden['celular']
."\n: ". $rowOrden['ciudad']
."\n: ". $rowOrden['pais']
."\n: ". $rowOrden['casilla']
, 0, '', 0, 1, '', '', true);

$pdf->SetXY(90, 19);
$pdf->SetFont('helvetica', 'B', 8.5);
$pdf->MultiCell(35, 0, 'Enviar a / Send to'
."\nDirección / Address"
."\nCiudad (City),ST ZIP"
."\nPaís / Country"
."\nCentro / Almacén"
."\nLugar de entrega"
."\nPaís  / Country"
."\nDirección de entrega"
."\nContacto"
, 0, '', 0, 1, '', '', true);

$pdf->SetXY(125, 19);
$pdf->SetFont('helvetica', '', 8.5);
$pdf->MultiCell(75, 0, ': '. $rowOrden['enviar']
."\n: " . $rowOrden['direccionDestino']
."\n: " . $rowOrden['ciudadDestino']
."\n: " . $rowOrden['paisDestino']
."\n: " . $rowOrden['almacen']
."\n: " . $rowOrden['lugar']
."\n: " . $rowOrden['direccionEntrega']
."\n: " . $rowOrden['contacto']
, 0, '', 0, 1, '', '', true);
$y = 55;
$pdf->Line(5, $y, $pdf->GetPageWidth()-5, $y);

$pdf->SetFont('helvetica', 'B', 8.5);
$pdf->SetXY(5, 56);
$pdf->Cell(50, 0, 'INFORMACIÓN DEL PROVEEDOR', 0, 1, 'L', 0, '', 0);
$pdf->SetXY(5, 60);
$pdf->MultiCell(30, 0, "Razón social"
."\nDirección a"
."\nRUC"
."\nTeléfono"
."\nZip Code"
."\nAtención"
."\nMail"
, 0, '', 0, 1, '', '', true);

$pdf->SetFont('helvetica', '', 8.5);
$pdf->SetXY(35, 60);
$pdf->MultiCell(80, 0, ': '. $rowProveedor['razon']
."\n: " . $rowProveedor['direccionDestino']
."\n: " . $rowProveedor['ruc']
."\n: " . $rowProveedor['celular']
."\n: " . $rowProveedor['zip']
."\n: " . $rowProveedor['atencion']
."\n: " . $rowProveedor['correo']
, 0, '', 0, 1, '', '', true);

$pdf->SetFont('helvetica', 'B', 8.5);
$pdf->SetXY(115, 60);
$pdf->MultiCell(30, 0, "Fecha de emisión"
."\nFormas de pago"
."\nReferencia"
."\nTipo moneda"
."\nIncoterm"
, 0, '', 0, 1, '', '', true);

$pdf->SetFont('helvetica', '', 8.5);
$pdf->SetXY(145, 60);
$pdf->MultiCell(40, 0, ': '. fechaLatam($rowProveedor['emision'])
."\n: " . deuda($rowProveedor['diasPago'])
."\n: " . $rowProveedor['referencia']
."\n: " . $rowProveedor['moneda']
."\n: " . $rowProveedor['incoterm']
, 0, '', 0, 1, '', '', true);


$pdf->SetXY(5, 90);
$tbl = '
<table cellspacing="0" cellpadding="1" border="1">
    <tr>
        <td rowspan="3">COL 1 - ROW 1<br />COLSPAN 3</td>
        <td>COL 2 - ROW 1</td>
        <td>COL 3 - ROW 1</td>
    </tr>
    <tr>
        <td rowspan="2">COL 2 - ROW 2 - COLSPAN 2<br />text line<br />text line<br />text line<br />text line</td>
        <td>COL 3 - ROW 2</td>
    </tr>
    <tr>
       <td>COL 3 - ROW 3</td>
    </tr>

</table>';

$pdf->writeHTML($tbl, true, false, false, false, '');

/* 
$x=0; $y=0;
$pdf->cell(0, 0, 'ORDEN DE COMPRA', 1,1,'R',0,'',0);
$pdf->Cell(0, 10, '¡Hola, mundo! '. $rowOrden['razon'] .' Este es mi primer PDF creado con TCPDF.', 1, 1, 'C', 0, '', 0, false, 'T', 'M');
 */
// Salida del PDF
$pdf->Output('example.pdf', 'I');