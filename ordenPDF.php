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

$pdf->SetFont('helvetica', '', 7.5);
$pdf->SetXY(5, 90);
$sumaNeto = 0;
$tbl = '
<table cellspacing="0" cellpadding="1" border="1" >
    <tr>
        <td style="text-align:center"><strong>SOLPED</strong></td>
        <td style="text-align:center" style="width:4%"><strong>Pos</strong></td>
        <td style="text-align:center" style="width:4%"><strong>Cod. Espe</strong></td>
        <td style="text-align:center" style="width:25%"><strong>Descripción Material</strong></td>
        <td style="text-align:center"><strong>F. Entr</strong></td>
        <td style="text-align:center" style="width:5%"><strong>Cant</strong></td>
        <td style="text-align:center"><strong>U. Med</strong></td>
        <td style="text-align:center"><strong>P. Unit.</strong></td>
        <td style="text-align:center"><strong>P. Total</strong></td>
    </tr>
';

foreach($productos as $producto){
    $sumaNeto += $producto['cantidad'] * $producto['precioUnitario'];
    $obs = $producto['observaciones'];
    $tbl .= "<tr>
        <td style='text-align:center'>{$producto['solped']}</td>
        <td style='text-align:center'>{$producto['pos']}</td>
        <td style='text-align:center'>{$producto['codigo']}</td>
        <td style='text-align:center'>{$producto['descripcion']} ". ($obs!='' ? "<br>Obs.: ".$obs  : '' ) ." </td>
        <td style='text-align:center'>".fechaLatam($producto['fecha'])."</td>
        <td style='text-align:center'>{$producto['cantidad']}</td>
        <td style='text-align:center'>{$producto['medida']}</td>
        <td style='text-align:center'>{$producto['precioUnitario']}</td>
        <td style='text-align:center'>". number_format($producto['cantidad'] * $producto['precioUnitario'], 2) ."</td>
    </tr>"; 
}
$igv = $sumaNeto*0.18;
$moneda =  $rowProveedor['moneda']=='1' ? 'PEN': 'USD';
$sumaTotal = $sumaNeto + $igv;
$tbl .= '<tr><td style="border-style:hidden" colspan="6"></td>
    <td style="border-style:none">
        <strong>Neto</strong>
        <br><strong>Dcto. %</strong>
        <br><strong>Cargos</strong>
    </td>
    <td style="border-style:none">'.
    "{$moneda}<br>{$moneda}<br>{$moneda}"
    .'</td>
    <td style="border-style:none">'. number_format($sumaNeto,2).'<br>0.00<br>0.00</td>
</tr>';
$tbl .= '<tr><td style="border-style:hidden" colspan="6"></td>
    <td style="border-style:none">
        <strong>SUB</strong>
        <br><strong>IGV 18%</strong>
        <br><strong>TOTAL</strong>
    </td>
    <td style="border-style:none">'.
    "{$moneda}<br>{$moneda}<br>{$moneda}"
    .'</td>
    <td style="border-style:none">'. number_format($sumaNeto,2).'<br>'. number_format($igv,2).'<br>'. number_format($sumaTotal,2).'</td>
</tr>';

$tbl .= '</table>';
$pdf->writeHTML($tbl, true, false, false, false, '');

$pdf->SetXY(5, 250);
$pdf->SetFont('helvetica', 'B', 8.5);
$pdf->MultiCell(0, 10, 'Observaciones: '. ucwords($rowOrden['observaciones']), 1, 1, '');
$pdf->MultiCell(0, 0, 'Datos comprador: '. ucwords($rowOrden['contacto']) . " Teléfono contacto: " . $rowOrden['telefonoComprador'] . " Email: " . $rowOrden['correoComprador'] , 0, 1, '');
$pdf->MultiCell(0, 0, "Aprobador 1: {$rowOrden['aprobador1']}. Aprobador 2: {$rowOrden['aprobador2']}. Aprobador 3: {$rowOrden['aprobador3']} "
, 0, 1, '');

$pdf->AddPage();
$pdf->SetXY(0, 5);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->MultiCell(0, 5, "CONDICIONES GENERALES DE COMPRA", 0, 'C');

$pdf->SetFont('helvetica', 'B', 7);
$pdf->MultiCell(90, 0, "1. ANTECEDENTES", 0, '');

$pdf->SetFont('helvetica', '', 7);
$pdf->MultiCell(90, 0, "El presente documento establece las condiciones generales (las #Condiciones Generales#) que regirán la relación jurídica y comercial para la adquisición de el/los bien(es) (los #Bienes#) y eventuales servicios asociados con la compra de dichos Bienes (los #Servicios#) que se especifican en la Orden de Compra (la #Orden#) GRUPO CIMAL Perú S.A. (en adelante, #CIMAL#), cuyos datos se detallan en la Orden, entregue al #PROVEEDOR# (según está definido en la Orden). CIMAL y el PROVEEDOR podrán ser denominados, según el contexto, individualmente como la #Parte# o conjuntamente como las #Partes#. Toda referencia a los Servicios, se entenderá que aplica sólo en caso que en efecto se haya contratado la prestación de servicios asociados a los Bienes. La Orden, sus anexos y las presentes Condiciones Generales conforman el contrato (conjuntamente el #Contrato#).\nEl PROVEEDOR manifiesta su aceptación con las presentes Condiciones Generales mediante su firma al final del documento. Sin embargo, en caso que el PROVEEDOR no incluya su firma y empiece a ejecutar las prestaciones a su cargo o transcurran setenta y dos (72) horas desde la fecha de su envío al PROVEEDOR, vía correo electrónico, sin que éste informe a CIMAL sus observaciones u objeciones a las mismas, lo que ocurra primero, se entenderá para todos los efectos que el PROVEEDOR acepta íntegramente las presentes Condiciones Generales, así como la Orden y sus anexos. \nNinguno de los términos, condiciones, excepciones o aclaraciones indicados por el PROVEEDOR en su cotización, propuesta u otros documentos, ya sean verbales o escritos, serán vinculantes a menos que sean incorporados expresamente y por escrito en el Contrato por parte de CIMAL. \nLas presentes Condiciones Generales son un documento plenamente vinculante entre las Partes, según el orden de prelación indicado en la sección 2 siguiente. \n El Contrato constituye e incorpora el acuerdo y entendimiento total entre las Partes en relación con los asuntos que contiene, a la fecha del Contrato, y sustituye a cualquier otro acuerdo y declaración anterior, incluyendo los documentos de cualquier licitación o convocatoria, sea verbal o escrita, y con independencia de que se hubieran hecho de forma negligente o de buena fe (excluidas expresamente las declaración. \n", 0, 'J');

$pdf->SetXY(10, 115);
$pdf->Ln(2);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->MultiCell(90, 0, "2. EL CONTRATO Y SUS ANEXOS", 0, '');


$pdf->SetFont('helvetica', '', 7);
$pdf->MultiCell(90, 0, "El Contrato comprende los siguientes documentos, los cuales en caso de conflicto o inconsistencia serán interpretados en el siguiente orden de prelación:\n1ro. La Orden\n 2do. Especificaciones de los Bienes y Servicios (Las #Especificaciones#), en caso hayan. \n 3ro. Las Condiciones Generales\n4to. Guía de Cumplimiento en Seguridad, Salud Ocupacional y Medio Ambiente para Contratistas y Proveedores\n5to. Propuesta Económica (excluyendo las condiciones generales de contratación u otras condiciones del PROVEEDOR, si las hubiera, las cuales no forman parte del Contrato) \n6to Cualquier otro documento incluido en el Contrato expresamente por referencia en la Orden, las Especificaciones o las presentes Condiciones Generales\n", 0, 'J');
$pdf->Ln(1);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->MultiCell(90, 0, "3. ALCANCE DEL CONTRATO", 0, '');


$pdf->SetFont('helvetica', '', 7);
$pdf->MultiCell(90, 0, "El alcance del Contrato, de los Bienes y Servicios incluye todos aquellos bienes y servicios que, mencionados o no en el Contrato, sean necesarios o convenientes para el cumplimiento de la finalidad del Contrato, o que de sus términos pueda razonablemente inferirse que lo son. Adicionalmente, los Bienes y Servicios deberán cumplir con la normativa legal aplicable al momento de culminación de la prestación, códigos, estándares y con las Buenas Prácticas de la Industria.\nPara efectos del presente Contrato, Buenas Prácticas de la Industria significa el grado de diligencia, cuidado, habilidad, prudencia, anticipación y experiencia práctica que razonablemente y normalmente se puede esperar de un profesional /proveedor/fabricante/contratista diligente, experimentado, calificado y competente con experiencia en la elaboración, distribución, transporte, suministro de bienes o la prestación de servicios, según sea el caso, que sean similares a los Bienes y Servicios en dimensiones, naturaleza y complejidad, actuando conforme a la legislación aplicable y a las prácticas, métodos, especificaciones, normas de seguridad, diseño y servicios equivalentes a nivel internacional y en especial en el Perú\nEl PROVEEDOR es responsable de proveer a su cuenta, costo y riesgo todos los medios necesarios para el cumplimiento del Contrato (incluyendo, sin limitación, todos los equipos, materiales, herramientas, y personal suficientemente calificado), salvo aquellas obligaciones que estén expresamente indicadas como responsabilidad de CIMAL. \nEl plazo para la entrega de los Bienes y la ejecución de los Servicios será el indicado en la Orden o en las Especificaciones.\n", 0, 'J');

$pdf->Ln(1);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->MultiCell(90, 0, "4. OBLIGACIONES Y DECLARACIONES DEL PROVEEDOR", 0, '');


$pdf->SetFont('helvetica', '', 7);
$pdf->MultiCell(90, 0, "Deberá cumplir con la entrega de los Bienes y Servicios de acuerdo con las Especificaciones, plazos, condiciones y demás estipulaciones establecidas en el Contrato, así como con las Buenas Prácticas de la Industria. En caso apliquen diversos estándares, primará el que sea más estricto en favor de CIMAL\n- Deberá cumplir con las normas legales, tributarias, laborales, ambientales, seguridad y/o todas las otras normas legales aplicables al Contrato.\n- Deberá cumplir con las normas y políticas de CIMAL relacionadas con temas de ética, seguridad, salud ocupacional y medio ambiente aplicables a la compra de Bienes y la prestación de los Servicios. \n", 0, 'J');

$pdf->SetXY(100, 14);
$pdf->MultiCell(90, 0, "- Ha tomado conocimiento de todos los hechos y circunstancias relevantes para la ejecución de sus obligaciones. En ningún caso, el PROVEEDOR tendrá derecho a beneficio alguno debido a la falta de información con relación a las condiciones para la compra de los Bienes y ejecución de los Servicios que haya debido prever conforme a las Buenas Prácticas de la Industria o que haya podido averiguar o confirmar previamente.\n- Deberá contar con todas las autorizaciones, permisos y licencias que fuesen necesarias para la entrega de los Bienes y/o la prestación de los Servicios de acuerdo con las leyes aplicables.\n- Deberá, en caso preste Servicios en las instalaciones de CIMAL, cumplir con la #Guía De Cumplimiento en Seguridad, Salud Ocupacional y Medio Ambiente para Contratistas y Proveedores# que forma parte de este Contrato. \n- El PROVEEDOR deberá mantener toda la información y documentos relacionados con la compra de los Bienes y/o eventuales Servicios por un mínimo de cinco (5) años luego de la fecha de culminación de la ejecución de las prestaciones contractuales o por un tiempo mayor en caso lo requiera la normativa aplicable, lo que resulte mayor. El PROVEEDOR garantiza que todos los archivos y registros necesarios para comprobar el cumplimiento del Contrato, incluyendo, sin limitación, los aspectos de seguridad, salud, higiene y medio ambiente, estarán siempre a disposición de CIMAL.\n- El PROVEEDOR entregará como parte de los Bienes y/o Servicios todos los manuales de operación y mantenimiento, planos, dibujos, cálculos, documentación técnica, diagramas lógicos, reportes de avance, certificados de calidad, cartas de porte, cartas de embarque, certificados de origen, autorizaciones de exportación y licencias, y cualquier otro documento requerido en el Contrato o que razonablemente pueda inferirse, o que sea requerido por la normativa aplicable o las Buenas Prácticas de la Industria. En caso que CIMAL lo requiera, el PROVEEDOR deberá aprobación. La ejecución del Contrato no se considerará completa hasta entregar cualquiera de dichos documentos a CIMAL para su revisión y que se haya entregado toda la documentación requerida de acuerdo con la misma. \n- El PROVEEDOR deberá poner a disposición de CIMAL, sin costo alguno todas las especificaciones relacionadas con los Bienes y/o Servicios\n", 0, 'J');

$pdf->SetXY(100, 117);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->MultiCell(90, 0, "5. SUPERVISION Y PRUEBAS", 0, '');

$pdf->SetXY(100, 121);
$pdf->SetFont('helvetica', '', 7);
$pdf->MultiCell(90, 0, "El PROVEEDOR implementará un adecuado y reconocido programa de control de calidad para garantizar que los Bienes y/o Servicios cumplan con los requerimientos del Contrato y entregará a CIMAL todos los certificados de pruebas y otra documentación que sea requerida en virtud al Contrato o que CIMAL razonablemente requiera.\nAl momento de la aceptación de los Bienes y la prestación de los Servicios, CIMAL podrá, según lo considere, inspeccionarlos o probarlos en dicho momento o en cualquier momento posterior. Si el Contrato incluye la realización de pruebas o inspecciones a los Bienes y/o Servicios, la ejecución del Contrato no se considerará completa hasta que dichas pruebas o inspecciones hayan sido superadas a entera satisfacción de CIMAL. \nNi la aprobación o aceptación por parte de CIMAL de cualquier prueba a los Bienes o Servicios, ni cualquier inspección o prueba realizada por CIMAL, ni la no realización u omisión de las mismas, liberará al PROVEEDOR de su responsabilidad de cumplir con el Contrato ni implicará la aceptación por parte de CIMAL de los Bienes y/o eventuales Servicios, ni liberará al PROVEEDOR de cualquier otra responsabilidad que bajo el Contrato o las leyes aplicables le corresponda. \nNi el aprovechamiento, ni el uso de los Bienes y Servicios, ni el pago por CIMAL supone su aceptación de los Bienes y/o Servicios o renuncia a algún derecho. Solo se entenderá que hay aceptación de los Bienes y/o Servicios cuando CIMAL lo señale expresamente por escrito.\n", 0, 'J');

$pdf->SetXY(100, 190);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->MultiCell(90, 0, "6. CONTRAPRESTACIÓN Y TRIBUTOS", 0, '');

$pdf->SetXY(100, 194);
$pdf->SetFont('helvetica', '', 7);
$pdf->MultiCell(90, 0, "La contraprestación indicada en la Orden representa la única contraprestación a la que tiene derecho el PROVEEDOR por los Bienes y Servicios. Dicho precio incluye: los costos de entrega de los Bienes y la prestación de los Servicios; todos los tributos (Salvo por el Impuesto General a las Ventas,) derechos, aranceles y similares; todos los costos y gastos relacionados con la ejecución, incluyendo, pero no limitado a, gastos de viaje, contribuciones o pagos a cualquier organización, primas de seguro, y de manera general cualquier riesgo, costo, gasto o contingencia del PROVEEDOR. CIMAL efectuará las retenciones y deducciones que correspondan según la normativa legal aplicable, sin que por ello corresponda incremento alguno al precio pactado.\nEl PROVEEDOR no podrá efectuar ningún cambio o variación al alcance del Contrato sin el consentimiento previo y por escrito de CIMAL.", 0, 'J');

$pdf->AddPage();
$pdf->SetXY(0, 5);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->MultiCell(0, 5, "CONDICIONES GENERALES DE COMPRA", 0, 'C');

$pdf->SetFont('helvetica', 'B', 7);
$pdf->MultiCell(90, 0, "7. PAGOS Y FACTURACION", 0, '');

$pdf->SetFont('helvetica', '', 7);
$pdf->MultiCell(90, 0, "A menos que se indique algo distinto en la Orden, el PROVEEDOR tendrá derecho a facturar el precio de los Bienes y Servicios sólo cuando los mismos hayan sido aceptados en su totalidad y sin observaciones por CIMAL conforme al Contrato.\nEl PROVEEDOR no tendrá derecho a compensar ninguno de los montos que adeude a CIMAL contra reclamos que pudiera tener contra CIMAL a no ser que dicho(s) reclamo(s) haya(n) sido consentido(s) expresamente y por escrito por CIMAL o haya(n) sido resuelto(s) totalmente y de manera definitiva a favor del PROVEEDOR, conforme a lo dispuesto en la cláusula referida a Resolución de Conflictos contenida en las presentes Condiciones Generales. \nEl PROVEEDOR deberá pagar todos sus tributos, impuestos, tasas, derechos, y contribuciones que le correspondan conforme a ley con relación al Contrato. El pago de las facturas se efectuará en el plazo que se indique en la Orden, contados desde la fecha de recepción de la factura a conformidad de CIMAL (esto es sin errores u observaciones por CIMAL). No se tramitará ninguna factura que no adjunte el original y/o copia de la Orden #debidamente firmada por CIMAL-, original y/o copia de estas Condiciones Generales #debidamente firmadas por EL PROVEEDOR- y la Guía de Remisión con constancia de recepción de la entrega de el/los Bien/es. \nEn caso el PROVEEDOR sea Emisor Electrónico designado por la SUNAT de acuerdo con las normas tributarias vigentes deberá emitir sus comprobantes de pago electrónicos (Facturas Electrónicas, Notas de Crédito Electrónicas, Notas de Débito Electrónicas o Recibos por Honorarios Electrónicos), salvo que se encuentre dentro de alguna de las causales de excepción establecidas en las normas aplicables, mediante el sistema electrónico que corresponda según las normas vigentes. Para cuyo efecto, el PROVEEDOR deberá tener en cuenta lo siguiente: \ni. Para fines comerciales, CIMAL solo aceptará y procesará el pago de los comprobantes de pago electrónicos que hayan sido previamente validados por la SUNAT, de acuerdo con los plazos o procedimientos establecidos en las normas vigentes. \nii. CIMAL considerará como fecha de recepción de los comprobantes de pago electrónicos el día que los mismos comprobantes de pago electrónicos en formato pdf y sus correspondientes archivos xml, sean recibidos facturaproveedores.espe@CIMAL.com. Los comprobantes de pago electrónicos recibidos los días sábado, domingo, feriados o días declarados no laborables por el Estado, se considerarán como recibidos el primer día hábil siguiente Es responsabilidad del PROVEEDOR garantizar la adecuada entrega de los comprobantes de pago electrónicos a la dirección indicada. El horario de recepción de los comprobantes de pago físicos será de lunes, miércoles y viernes de 9:00 am a 12 del mediodía, salvo feriados y/o días declarados no laborables por el Estado. \nEn los casos de facturas parciales se debe indicar el número de la orden de compra correspondiente, acompañada de la guía de despacho con timbre de recepción de CIMAL, fotocopia de la OC y fotocopia del PI* (Parte de Ingreso). Por total o saldo: en todos los casos de facturas por total o saldo se debe indicar el número de la OC correspondiente, acompañada de la guía de despacho con timbre de recepción de CIMAL y fotocopia del PI que debe ser emitido directamente por el usuario receptor de las mercaderías, servicios u otro al proveedor. \nLas facturas deberán ser extendidas por cada Nota de Recepción. \n", 0, 'J');

$pdf->SetFont('helvetica', 'B', 7);
$pdf->MultiCell(90, 0, "8. SEGUROS", 0, '');

$pdf->SetFont('helvetica', '', 7);
$pdf->MultiCell(90, 0, "El PROVEEDOR deberá contratar y mantener adecuada cobertura de seguros conforme a lo requerido por las leyes aplicables, el Contrato y las Buenas Prácticas de la Industria, todo en términos aceptables para CIMAL durante la vigencia del Contrato. El PROVEEDOR deberá entregar a CIMAL copia de las Pólizas de Seguro respectivas, dentro de los dos (2) días hábiles siguientes al perfeccionamiento del Contrato. El PROVEEDOR será responsable del pago de todos los deducibles y montos que correspondan a sus seguros. Salvo en caso que se indique algo distinto en la Orden o Especificaciones, el PROVEEDOR deberá asegurar bajo su cuenta, costo y riesgo los Bienes hasta el momento de su entrega a CIMAL. Dicho seguro debe contar con la cobertura que requiere el Contrato o con aquella que sea usual según las Buenas Prácticas de la Industria, incluyendo, pero no limitado a, pérdida, robo y daños.\n", 0, 'J');

$pdf->SetFont('helvetica', 'B', 7);
$pdf->MultiCell(90, 0, "9. GARANTÍA DE CALIDAD", 0, '');

$pdf->SetFont('helvetica', '', 7);
$pdf->MultiCell(90, 0, "El PROVEEDOR garantiza a CIMAL que: (i) los Bienes serán fabricados y suministrados y los Servicios prestados conforme a los requerimientos del Contrato, las Buenas Prácticas de la Industria y las leyes aplicables al momento de la aceptación de los mismos; (ii) los Bienes serán nuevos; (iii) los Bienes y Servicios serán aptos para los usos a que ordinariamente se destinen los bienes y servicios del mismo tipo; (iv) los Bienes y Servicios serán aptos para cualquier uso especial que expresa o tácitamente se haya hecho saber al PROVEEDOR o que éste deba haber inferido; (v) los Bienes estarán envasados o embalados en la forma habitual para tales bienes o, si no existe tal forma, de una forma adecuada para conservarlos y protegerlos desde su embalaje hasta su entrega y almacenamiento; (vi) los Bienes y Servicios estarán libres de todo defecto, deficiencia, imperfección o falla de cualquier tipo; y (vii) los Bienes se encontrarán libres de cargas, gravámenes y la propiedad será transferida plenamente y sin restricciones a CIMAL. El PROVEEDOR garantiza que, como parte de este Contrato, el PROVEEDOR transfiere a CIMAL los derechos de propiedad intelectual requeridos para que CIMAL haga uso pleno de los Bienes y/o Servicios, incluyendo el relativo a cualquier software asociado.\n", 0, 'J');

$pdf->SetXY(100, 13);
$pdf->MultiCell(90, 0, "Salvo indicación distinta en la Orden, el período de garantía por cualquier defecto (obligación de saneamiento de defectos y sus consecuencias) será de veinticuatro (24) meses computados desde la aceptación de los Bienes y Servicios por CIMAL, salvo que el Contrato incluya obras civiles, en cuyo caso el período de garantía por cualquier defecto será de sesenta (60) meses desde su aceptación respecto de dichas obras civiles. Los Bienes reemplazados o reparados y los Servicios re-ejecutados y las consecuencias de defectos saneadas estarán sujetos a un nuevo período de garantía. Si durante el período de garantía por defectos se identifica que alguna parte de los Bienes y/o Servicios es defectuosa o no cumple con el Contrato o causa daños, el PROVEEDOR deberá corregir los Bienes y/o Servicios defectuosos o no conformes y/o daños a su cuenta, costo y riesgo. \nSi el PROVEEDOR no cumple con remediar el defecto, no conformidad o daños respectivos con la debida diligencia y dentro del tiempo indicado por CIMAL (o en caso no se haya indicado, dentro de un período razonable luego del requerimiento de CIMAL), o si las circunstancias lo justifican razonablemente, CIMAL tendrá derecho a remediar los defectos, no conformidades o daños por sí mismo o a través de un tercero a cuenta, costo y riesgo del PROVEEDOR. En caso que el defecto, no conformidad o daño sea de tal envergadura que los Bienes y Servicios no puedan ser usados para el fin para el cual pretendían ser destinados o, dicho uso se encuentre significativamente afectado, o en caso de un defecto, no conformidad o daño recurrente, CIMAL podrá rechazar los Bienes y Servicios, y requerir el reembolso de lo pagado, más intereses. Los remedios indicados en esta cláusula no excluyen otros derechos y remedios que estén a disposición de CIMAL, incluyendo el derecho a resolver el Contrato. \nCIMAL podrá notificar sobre los defectos, no conformidades o daños descubiertos durante el período de garantía, en cualquier momento, en la medida que lo haga hasta treinta (30) días calendario después del vencimiento del período de garantía. Cualquier reclamo o remedio relacionado con defectos, no conformidades o daños que se notifiquen de acuerdo a lo indicado en este párrafo podrán ser hechos valer por CIMAL durante un período de cinco (5) años posteriores a la fecha en que CIMAL notificó sobre el defecto, no conformidad o daño. \nLas Partes acuerdan que, en lo que resulte aplicable, el PROVEEDOR será responsable por sus acciones negligentes frente a CIMAL, por lo cual las Partes pactan expresamente en contra de lo dispuesto en el artículo 1762 del Código Civil. \nSe debe especificar la retención del 10% del monto total adjudicado y contratado en la última valorización, por concepto de garantía de calidad y postventa por un periodo de 06 meses, posterior a la firma del acta de entrega de recepción de obra.\n", 0, 'J');


$pdf->SetXY(100, 145);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->MultiCell(90, 0, "10. PROPIEDAD INTELECTUAL E INDUSTRIAL.CONDICIONES GENERALES DE COMPRA", 0, '');


$pdf->SetXY(100, 151);
$pdf->SetFont('helvetica', '', 7);
$pdf->MultiCell(90, 0, "Todo material, componente, herramienta, diseño, equipo, consumible y cualquier otro bien o insumo que pertenezca a CIMAL o que sea provisto por CIMAL o terceros por encargo de CIMAL, que sea puesto a disposición de o al cuidado del PROVEEDOR con cualquier fin, será marcado y registrado por el PROVEEDOR como propiedad de CIMAL y durante el período que el PROVEEDOR lo tenga bajo su posesión estará bajo su responsabilidad. El PROVEEDOR no usará ninguno de dichos bienes para beneficio propio o de terceros sin el consentimiento previo y por escrito de CIMAL. En caso CIMAL lo requiera, incluyendo los casos de resolución del Contrato, el PROVEEDOR los devolverá o permitirá a CIMAL entrar a sus locales o a los de sus subcontratistas o proveedores para tomar posesión de dichos bienes o cualquier parte de los mismos. Los diseños, dibujos, especificaciones, instrucciones, manuales y otros documentos creados, producidos o encargados por CIMAL y entregados al PROVEEDOR para la ejecución del Contrato (colectivamente, los Documentos de CIMAL#), así como los derechos de autor sobre los mismos y todos los demás derechos sobre la propiedad intelectual relacionados con los mismos, son y serán propiedad de CIMAL.\nLos Documentos de CIMAL no serán utilizados por el PROVEEDOR para fines distintos a la ejecución del Contrato sin la autorización previa y por escrito de CIMAL. Los diseños, dibujos, especificaciones, instrucciones, manuales y otros documentos creados, producidos o encargados por o en nombre del PROVEEDOR en relación con el Contrato (colectivamente, los #Documentos del Servicio#), así como los derechos de autor sobre los mismos y todos los derechos sobre la propiedad intelectual relacionados con los mismos serán de propiedad de CIMAL.\nEl PROVEEDOR se obliga a no usar de forma alguna la denominación o razón social, marcas, ni otros elementos de propiedad industrial de CIMAL o de sus empresas vinculadas.\nEl PROVEEDOR deberá en todo momento eximir de responsabilidad y mantener indemne a CIMAL frente a todas y cada una de las acciones, reclamaciones, demandas, costos, cargos y gastos incurridos (incluyendo honorarios de abogados) o derivados de la infracción o presunta infracción de patentes, diseños registrados, propiedad industrial o intelectual, marca comercial o nombre comercial o cualquier otro derecho similar protegido en el Perú o en otro lugar por el uso o posesión de cualesquiera materiales, suministros, software u otro.\n", 0, 'J');

$pdf->AddPage();
$pdf->SetXY(0, 5);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->MultiCell(0, 5, "CONDICIONES GENERALES DE COMPRA", 0, 'C');

$pdf->SetFont('helvetica', '', 7);
$pdf->MultiCell(90, 0, "Sin perjuicio de lo anterior, si el uso o posesión de cualquier bien, pieza de material, equipo, software u otro constituye una infracción según lo descrito en el numeral anterior o su utilización fuera prohibida o afectada, el PROVEEDOR deberá, a su cuenta, costo y riesgo y según lo requiera CIMAL, bien procurar a CIMAL el derecho de continuar utilizando el citado bien,  pieza de material, equipo o software u otro o sustituirlo por un bien, pieza de material, equipo, software u otro de equivalente o de mejor calidad, que no infrinja derecho alguno o modificarlo de forma que no infrinja tales derechos.\n", 0, 'J');

$pdf->Ln(1);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->MultiCell(90, 0, "11. CONFIDENCIALIDAD", 0, '');

$pdf->SetFont('helvetica', '', 7);
$pdf->MultiCell(90, 0, "Toda la información relacionada a CIMAL y sus operaciones a las que tenga acceso el PROVEEDOR con ocasión del Contrato (la #Información Confidencial#) será de exclusiva propiedad de CIMAL y tendrá carácter confidencial. La Información Confidencial incluye, pero no se limita a, todos los documentos y demás información (ya sea escrita, oral, digital o de otro tipo) que haya sido entregada o a la que haya tenido acceso el PROVEEDOR en virtud a o con relación al Contrato.\nEl PROVEEDOR deberá garantizar que él mismo y sus trabajadores, subcontratistas, proveedores, asesores y representantes, y cada uno de sus respectivos sucesores y derechohabientes mantengan la confidencialidad del Contrato y la Información Confidencial, El PROVEEDOR no la publicará, ni revelará en forma alguna, ni utilizará para sus propios fines la Información Confidencial, excepto para cumplir las obligaciones especificadas en el Contrato.\nLas disposiciones del presente numeral no se aplicarán a: (a) la información que sea de dominio público y sea obtenida de otra forma que no sea mediante el incumplimiento de la presente obligación de confidencialidad; (b) la información que esté en posesión del PROVEEDOR, antes de la entrada en vigencia del presente Contrato, siempre que haya sido obtenida de CIMAL o terceros sin estar sujeta a una obligación de confidencialidad y que no esté relacionada con el Contrato.\nEl PROVEEDOR deberá destruir o, en caso lo requiera CIMAL, devolver toda la Información Confidencial que tuviera en su poder al término de la ejecución del Contrato.\nLas obligaciones establecidas en la presente cláusula continuarán en vigor por un período de diez (10) años luego de finalizada la vigencia del presente Contrato.\n", 0, 'J');

$pdf->Ln(1);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->MultiCell(90, 0, "12. CESIÓN Y SUBCONTRATACIÓN", 0, '');

$pdf->SetFont('helvetica', '', 7);
$pdf->MultiCell(90, 0, "CIMAL podrá ceder libremente total o parcialmente el Contrato o los derechos derivados del mismo, para lo cual el PROVEEDOR le concede su autorización previa al momento de entrada en vigencia del Contrato. El PROVEEDOR no podrá ceder el presente Contrato, ni total ni parcialmente, sin la previa autorización por escrito de CIMAL. El PROVEEDOR podrá subcontratar siempre y cuando cuente con la aprobación previa y por escrito de CIMAL, lo que no eximirá al PROVEEDOR del cumplimiento del Contrato.\n", 0, 'J');

$pdf->Ln(1);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->MultiCell(90, 0, "13. FUERZA MAYOR", 0, '');

$pdf->SetFont('helvetica', '', 7);
$pdf->MultiCell(90, 0, "Ni el PROVEEDOR ni CIMAL, serán responsables por el incumplimiento o el cumplimiento parcial tardío o defectuoso de sus obligaciones establecidas en el Contrato originado por causas de caso fortuito o fuerza mayor según el artículo 1315° del Código Civil.\nLa ocurrencia del evento de caso fortuito o fuerza mayor debe ser comunicada por la Parte afectada por el mismo, a la otra Parte, de manera indubitable por escrito, como máximo dos (2) días hábiles después de que ocurrió o conoció (o que debió conocer) sobre el evento, lo que suceda primero, debiendo incluir la información y documentación de sustento disponible a dicho momento, el plazo estimado de duración del evento y las acciones a tomar para mitigar o subsanar las consecuencias del mismo. La parte notificada podrá observar la calificación de fuerza mayor o sus efectos contractuales. \nEn caso de ocurrir un evento de caso fortuito o fuerza mayor, la Parte afectada a) realizará sus mejores esfuerzos, dentro de lo comercialmente razonable, para mitigar los efectos del evento, b) no tendrá derecho a suspender el cumplimiento de sus obligaciones más allá del periodo de duración del evento de fuerza mayor y de las obligaciones que hayan sido afectadas por la fuerza mayor; c) empleará todos sus esfuerzos razonables para subsanar su incapacidad para cumplir con sus obligaciones, d) mantendrá a la otra Parte informada de dichos esfuerzos de manera continua, y él dará aviso escrito a la otra Parte de la reanudación del cumplimiento de sus obligaciones. \nEl PROVEEDOR será responsable por cualquier daño o pérdida causados a CIMAL que resulten de su demora injustificada en reiniciar la ejecución de sus obligaciones luego de concluido el evento respectivo.\nEn el caso de eventos de caso fortuito o fuerza mayor que afecten a los subcontratista o proveedores del PROVEEDOR, el PROVEEDOR sólo quedará liberado de cumplir con sus obligaciones en caso que el referido evento cumpla con los requisitos establecidos en la presente cláusula. Durante el caso fortuito o evento de fuerza mayor, cada Parte asumirá los costos que deba de incurrir por tal hecho.\n", 0, 'J');

$pdf->Ln(1);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->MultiCell(90, 0, "14. INCUMPLIMIENTOS Y PENALIDADES", 0, '');

$pdf->SetFont('helvetica', '', 7);
$pdf->MultiCell(90, 0, "El hecho que alguna de las Partes no insista en/o demore en exigir el cumplimiento de los términos, condiciones o estipulaciones del Contrato, o conceda cualquier otra indulgencia a la otra Parte no se considerará como una renuncia a las acciones correspondientes por el incumplimiento o como una aceptación de cualquier variación al Contrato o como una renuncia a alguno de los derechos estipulados en el Contrato, los cuales permanecerán en plena vigencia.\n", 0, 'J');

$pdf->SetXY(100, 10);
$pdf->MultiCell(0, 5, "Si el PROVEEDOR no cumple con la entrega de los Bienes y/o ejecución de los Servicios en el/los plazo/s y condiciones indicados en el Contrato quedará\n automáticamente constituido en mora desde el día siguiente de la fecha establecida, de acuerdo a lo establecido por el Artículo 1333º y siguientes del Código Civil peruano.\nPor otro lado, frente a dicha situación de incumplimiento, CIMAL podrá rechazar los Bienes y/o Servicios, sin que ello le genere obligación de pago o indemnización alguna a favor del PROVEEDOR, y obtener los Bienes y/o Servicios de un tercero, en cuyo caso, sin perjuicio de las penalidades a que hubiere lugar, el PROVEEDOR deberá pagar a CIMAL los sobrecostos y gastos incurridos para ello.\nEn caso de retraso en la entrega de los Bienes y/o la culminación de los Servicios, independientemente de que CIMAL opte por la resolución o por exigir el cumplimiento, el PROVEEDOR deberá pagar a CIMAL por cada día de atraso una penalidad por demora equivalente al 1% del valor total del Contrato (salvo que se detalle otro monto o porcentaje en la Orden), hasta la aceptación de los Bienes y/o Servicios o hasta la fecha de resolución del Contrato, si CIMAL opta por ello, según sea el caso. Dicha penalidad, y de ser el caso, la que se precise en la Orden será sin perjuicio de la responsabilidad del PROVEEDOR por el daño ulterior y los daños que pudieran ser producto de la resolución contractual. \nEl PROVEEDOR faculta a CIMAL a retener y compensar, total o parcialmente, cualquier importe que le deba pagar CIMAL contra cualquier penalidad, daño o perjuicio, o cualquier otra suma de dinero que el PROVEEDOR adeude a CIMAL y/o terceros.\n", 0, 'J');

$pdf->SetXY(100, 76);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->MultiCell(90, 0, "14. INCUMPLIMIENTOS Y PENALIDADES", 0, '');

$pdf->SetXY(100, 81);
$pdf->SetFont('helvetica', '', 7);
$pdf->MultiCell(0, 5, "Si el PROVEEDOR no cumple con la entrega de los Bienes y/o ejecución de los Servicios en el/los plazo/s y condiciones indicados en el Contrato quedará\n automáticamente constituido en mora desde el día siguiente de la fecha establecida, de acuerdo a lo establecido por el Artículo 1333º y siguientes del Código Civil peruano.\nPor otro lado, frente a dicha situación de incumplimiento, CIMAL podrá rechazar los Bienes y/o Servicios, sin que ello le genere obligación de pago o indemnización alguna a favor del PROVEEDOR, y obtener los Bienes y/o Servicios de un tercero, en cuyo caso, sin perjuicio de las penalidades a que hubiere lugar, el PROVEEDOR deberá pagar a CIMAL los sobrecostos y gastos incurridos para ello.\nEn caso de retraso en la entrega de los Bienes y/o la culminación de los Servicios, independientemente de que CIMAL opte por la resolución o por exigir el cumplimiento, el PROVEEDOR deberá pagar a CIMAL por cada día de atraso una penalidad por demora equivalente al 1% del valor total del Contrato (salvo que se detalle otro monto o porcentaje en la Orden), hasta la aceptación de los Bienes y/o Servicios o hasta la fecha de resolución del Contrato, si CIMAL opta por ello, según sea el caso. Dicha penalidad, y de ser el caso, la que se precise en la Orden será sin perjuicio de la responsabilidad del PROVEEDOR por el daño ulterior y los daños que pudieran ser producto de la resolución contractual. \nEl PROVEEDOR faculta a CIMAL a retener y compensar, total o parcialmente, cualquier importe que le deba pagar CIMAL contra cualquier penalidad, daño o perjuicio, o cualquier otra suma de dinero que el PROVEEDOR adeude a CIMAL y/o terceros.\n", 0, 'J');

$pdf->SetXY(100, 146);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->MultiCell(90, 0, "15. INDEMNIDAD Y RESPONSABILIDADCONDICIONES GENERALES DE COMPRA", 0, '');

$pdf->SetXY(100, 152);
$pdf->SetFont('helvetica', '', 7);
$pdf->MultiCell(0, 5, "El PROVEEDOR será responsable de cualquier daño o perjuicio causado a terceros y trabajadores del PROVEEDOR, que derive de sus acciones u omisiones, obligándose a mantener indemne, indemnizar y proteger a CIMAL, sus accionistas, apoderados, representantes, empleados, trabajadores y agentes frente a cualquier reclamo presentado en su contra por cualquier tercero.\nSalvo en caso de dolo o negligencia grave, CIMAL no será responsable frente al PROVEEDOR por lucro cesante, pérdida de oportunidad, daños indirectos, mediatos, consecuenciales o punitivos. Asimismo, salvo en caso de dolo o negligencia grave, la responsabilidad de CIMAL frente al PROVEEDOR, bajo el presente Contrato, no podrá exceder el precio pagado al PROVEEDOR.\n", 0, 'J');


$pdf->SetXY(100, 185);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->MultiCell(90, 0, "16. SUSPENSIÓN Y RESOLUCIÓN DEL CONTRATO.", 0, '');

$pdf->SetXY(100, 188);
$pdf->SetFont('helvetica', '', 7);
$pdf->MultiCell(0, 5, "16.1 En cualquier momento CIMAL podrá ordenar al PROVEEDOR la suspensión de todo o parte del Contrato mediante notificación al PROVEEDOR. El PROVEEDOR deberá tomar todas las medidas que sean necesarias o convenientes para minimizar los costos, gastos y demoras relacionados con dicha suspensión. En caso que y en la medida que la suspensión supere los tres (3) meses continuos CIMAL reembolsará al PROVEEDOR los costos directos (excluyendo cualquier elemento de lucro o margen) atribuible a dicha suspensión, siempre y cuando los mismos sean razonables y se encuentren debidamente documentados. El PROVEEDOR no suspenderá la ejecución del Contrato bajo ninguna razón, excepto con el consentimiento expreso y por escrito CIMAL.\n16.2 Salvo en los casos indicados en las secciones 16.3, 16.4 y 16.5 siguientes, cualquiera de las Partes podrá resolver el Contrato en caso de incumplimiento de una obligación material de la otra Parte, cursando una notificación con treinta (30) días de anticipación. En caso que la obligación incumplida sea una obligación de pago, la misma debe encontrarse vencida por al menos treinta (30) días para considerarse un incumplimiento material. 16.3 Sin perjuicio de cualesquiera otros derechos y remedios de CIMAL, CIMAL podrá, sin que ello implique responsabilidad alguna para él, resolver todo o parte del Contrato, de manera inmediata y de pleno derecho mediante notificación escrita al PROVEEDOR si: (i) el PROVEEDOR se encontrase significativamente retrasado y CIMAL le haya notificado su intención de resolver el Contrato; (ii) se inicia al PROVEEDOR o alguna de sus empresas vinculadas un procedimiento concursal ordinario, preventivo o cualquier otro de naturaleza concursal, o si el PROVEEDOR o alguna de sus empresas vinculadas es declarada en quiebra, o si el PROVEEDOR o alguna de sus empresas vinculadas entra en liquidación judicial o extrajudicial, o acuerda disolverse; o (iii) si se produjera un cambio de control societario del PROVEEDOR, de manera directa o indirecta, o se produjera cualquier situación análoga.\n", 0, 'J');

$pdf->AddPage();
$pdf->SetXY(0, 5);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->MultiCell(0, 5, "CONDICIONES GENERALES DE COMPRA", 0, 'C');

$pdf->SetFont('helvetica', '', 7);
$pdf->MultiCell(90, 0, "16.4 CIMAL tendrá derecho a, en cualquier momento resolver el Contrato, total o parcialmente, de manera unilateral y sin expresión de causa, mediante el envío al PROVEEDOR de una notificación con quince (15) días de anticipación. La resolución a que se refiere el presente numeral, no da lugar al pago de indemnización alguna a favor del PROVEEDOR, salvo lo indicado en el numeral 18.6.3.\n16.5 El COMPRADOR tendrá derecho a resolver el Contrato, total o parcialmente, en caso su ejecución se viera impedida sustancialmente por un caso fortuito o evento de fuerza mayor por un período continuo de noventa (90) días, mediante el envío al PROVEEDOR de una notificación con diez (10) días de anticipación. La resolución a que se refiere el presente numeral, no da lugar al pago de indemnización alguna a favor del PROVEEDOR, salvo lo indicado en el numeral 16.6.1. \n16.6 Consecuencias de la Resolución\n16.6.1 Cuando resulte aplicable, tan pronto como CIMAL lo requiera (y en la media que lo requiera), el PROVEEDOR deberá entregar a CIMAL los Bienes (terminados o en proceso de fabricación o adquisición), todos los documentos relacionados, información y derechos relacionados con los Bienes que requiera CIMAL para alcanzar la finalidad original del Contrato, directamente o a través de terceros; y hacer y conseguir todo lo que sea necesario para que CIMAL reciba y pueda mantener la propiedad de los Bienes. El PROVEEDOR tendrá derecho a que CIMAL le pague la parte del precio que sea equivalente a los Bienes y Servicios entregados o ejecutados conforme el Contrato, en la medida en que dicho pago se encontrase pendiente.\n16.6.2 En caso de resolución por incumplimiento del PROVEEDOR, CIMAL podrá, a su elección, rechazar todo o parte de los Bienes y Servicios, y/o completar los Bienes y Servicios en todo o en parte directamente o a través de terceros por cuenta, costo y riesgo del PROVEEDOR, sin perjuicio del derecho de CIMAL de oponer las compensaciones a que hubiera lugar por los daños y perjuicios que hubiere sufrido como resultado de la resolución del Contrato.\n16.6.3 En caso de resolución unilateral sin expresión de causa (y en la medida en que no hubiera sido cubierto por lo indicado en el numeral 16.6.1) el PROVEEDOR tendrá derecho a que CIMAL le pague un monto proporcional a los costos directos que fueran inevitables en que hubiera incurrido con anterioridad a la terminación, siempre y cuando dichos montos se encuentren claramente definidos, sustentados y no excedan, en su conjunto, el precio del saldo por pagar del Contrato. En cualquier caso, el PROVEEDOR procurará minimizar los costos y gastos que se generen como consecuencia de la resolución. Salvo por los pagos indicados en este párrafo, el PROVEEDOR no tendrá derecho a recibir compensación adicional de CIMAL.\n", 0, 'J');

$pdf->Ln(1);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->MultiCell(90, 0, "17. SOLUCIÓN DE CONTROVERSIAS", 0, '');

$pdf->SetFont('helvetica', '', 7);
$pdf->MultiCell(90, 0, "En caso de surgir cualquier conflicto derivado directa o indirectamente de o con relación al Contrato, incluyendo, pero sin limitarse a su interpretación, aplicación, ejecución, término, nulidad o anulabilidad, entre otros, las Partes trataran, sin estar obligados a, de llegar a un acuerdo de manera directa. Sin perjuicio de ello, y sin que resulte necesaria la negociación directa si alguna de las Partes no lo considera conveniente, para controversias por montos iguales o menores a US$50,000.00 (Cincuenta Mil y 00/100 Dólares de los Estados Unidos de América) estas serán sometidas a la jurisdicción de las cortes y tribunales de Lima # Cercado. En caso la controversia sea por un monto superior a US$50,000.00 (Cincuenta Mil y 00/100 Dólares de los Estados Unidos de América) las partes se someterán a un arbitraje de derecho, administrado por la Cámara de Comercio de Lima y regido por su Reglamento de Arbitraje, Será de aplicación supletoria el Decreto Legislativo No. 1071, y sus modificatorias. La sede y lugar del arbitraje serán la ciudad de Lima, Perú. El arbitraje será conducido en idioma Castellano. El Tribunal Arbitral estará integrado por tres (3) miembros que necesariamente deben ser abogados habilitados para ejercer el derecho en el Perú. Cada Parte designará a un (1) árbitro, y el tercero será designado por acuerdo de los dos (2) árbitros designados por las Partes, quien a su vez se desempeñará como Presidente del Tribunal Arbitral. La parte demandante deberá nominar a su árbitro en la solicitud de arbitraje y la parte demandada deberá nominar a su árbitro dentro del plazo de diez (10) días hábiles contados desde la fecha en que sea notificada con la solicitud de arbitraje.\nEl laudo que emita el Tribunal Arbitral será la última instancia y, en consecuencia, será definitivo e inapelable, de cumplimiento obligatorio a partir de la fecha de su notificación, renunciando las Partes de la manera más amplia que permitan las leyes aplicables e interponer cualquier medio impugnatorio contra el laudo, salvo las solicitudes de rectificación, interpretación, integración y exclusión ante el propio Tribunal Arbitral y el recurso de anulación de laudo ante el Poder Judicial. Para todo lo que resulte necesario la intervención o colaboración judicial con relación al arbitraje, incluida la ejecución del laudo arbitral, las Partes se someten a la jurisdicción de las cortes y tribunales del Distrito Judicial de Lima # Cercado\n", 0, 'J');

$pdf->Ln(1);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->MultiCell(90, 0, "18. LEY APLICABLE", 0, '');


$pdf->SetFont('helvetica', '', 7);
$pdf->MultiCell(90, 0, "Salvo que se pacte lo contrario, las leyes aplicables a la ejecución e interpretación del Contrato serán las leyes de la República del Perú, a excepción de la regulación sobre aplicación de derecho extranjero en casos de derecho internacional privado.\n", 0, 'J');

$pdf->SetXY(100, 10);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->MultiCell(90, 0, "19. ÉTICA, RESPONSABILIDAD AMBIENTAL Y SOCIAL", 0, '');

$pdf->SetXY(100, 13);
$pdf->SetFont('helvetica', '', 7);
$pdf->MultiCell(90, 0, "El PROVEEDOR reconoce que ha sido informado de, y acuerda cumplir con, los compromisos de CIMAL en el ámbito de la ética, la responsabilidad ambiental y social, ya que dichos compromisos han sido establecidos en la Carta Ética y en la Guía Práctica de Ética; los cuales se encuentran disponibles en la página web de CIMAL: https://www.grupocimalperu.pe/.\nEl PROVEEDOR declara y garantiza a CIMAL que, durante un período de seis (6) años inmediatamente anteriores a la ejecución del Contrato, ha cumplido con las normas del derecho internacional y derecho nacional aplicable al Contrato, en relación con:\ni. los derechos humanos fundamentales y en particular la prohibición de (i) el uso de trabajo infantil y cualquier forma de trabajo forzoso u obligatorio, y (ii) la organización de cualquier tipo de discriminación dentro de su empresa o hacia sus proveedores y subcontratistas; \nii. los embargos, la producción y comercialización de drogas, el tráfico de armas y el terrorismo; \niii. comercio, licencias y aduanas de importación y exportación; \niv. la salud y seguridad de su personal y de terceros;\nv. la mano de obra, la inmigración y la prohibición del trabajo ilegal;\nCONDICIONES GENERALES DE COMPRA\ni. la protección del medio ambiente; \nii. los delitos financieros, actos de corrupción en el ámbito de la administración pública y en el sector privado, el fraude, el tráfico de influencias (o infracción equivalente, de acuerdo a la ley nacional aplicable al Contrato), la estafa, el robo, la malversación de fondos corporativos, imitación, falsificación y el uso de falsificaciones y/o delitos similares o relacionados; \niii. el lavado de dinero y activos y las medidas para combatir dichas actividades; \niv. el derecho de la competencia. \nEn relación con la ejecución del Contrato, el PROVEEDOR se obliga a cumplir en su nombre y en nombre y por cuenta de sus proveedores y subcontratistas las mismas obligaciones establecidas en la presente cláusula. \nEl PROVEEDOR se encuentra prohibido de interactuar con entidades gubernamentales, autoridades públicas, personas o entidades privadas en nombre y representación de CIMAL. \nCIMAL tiene derecho a exigirle al PROVEEDOR, y el PROVEEDOR se encuentra obligado a brindar a CIMAL, evidencia de haber cumplido con las reglas de la presente cláusula de Ética, Responsabilidad Ambiental y Social. CIMAL tiene derecho a, y el PROVEEDOR se encuentra obligado a facilitar, que se lleven a cabo auditorías con relación a los aspectos que cubre la presente Cláusula, ya sea directamente o a través de terceros. Cualquier incumplimiento de las reglas de la presente Cláusula de Ética, Responsabilidad Ambiental y Social constituye un incumplimiento contractual grave que da derecho a CIMAL a suspender y/o terminar el Contrato; por cuenta y costo exclusivo del PROVEEDOR.\n", 0, 'J');

$pdf->SetXY(100, 146);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->MultiCell(90, 0, "20. TRANSPORTE", 0, '');


$pdf->SetXY(100, 149);
$pdf->SetFont('helvetica', '', 7);
$pdf->MultiCell(90, 0, "Independientemente de quien esté a cargo del transporte, el PROVEEDOR deberá cumplir con las instrucciones de embarque, embalaje y marcas y manejo de materiales provisto por CIMAL, sin perjuicio del cumplimiento de los requerimientos establecidos por la normatividad que resulten aplicables al respectivo tipo de transporte y a las Buenas Prácticas de la Industria. El PROVEEDOR deberá entregar a CIMAL, en la debida oportunidad, la documentación de transporte detallada y exacta en la medida que corresponda o lo requiera CIMAL.\nLos Bienes deberán ser entregados de acuerdo con los términos de envío incluidos en el Contrato o provistos por CIMAL a través de otros medios. Salvo que se señale algo distinto, los términos de envío serán interpretados de conformidad con los INCOTERMS 2000, o su equivalente para compra de Bienes locales. En caso que las Partes no pacten términos de envío, la entrega será en términos DDP INCOTERMS 2000 a ser entregados en la sede de CIMAL en Perú que indique CIMAL. \nEl PROVEEDOR será responsable del riesgo de pérdida o daño de los Bienes hasta el momento de su aceptación por parte de CIMAL, en el lugar de entrega establecido por las Partes en el Contrato. La propiedad de los Bienes se transferirá a CIMAL cuando los Bienes sean despachados para su envío a CIMAL o cuando CIMAL efectúe el pago correspondiente a todo o parte de los mismos, lo que ocurra primero.\n", 0, 'J');

$pdf->SetXY(100, 215);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->MultiCell(90, 0, "21. SERVICIOS ASOCIADOS", 0, '');

$pdf->SetXY(100, 218);
$pdf->SetFont('helvetica', '', 7);
$pdf->MultiCell(90, 0, "En caso que el Contrato implique la ejecución de Servicios, el PROVEEDOR debe cumplir con la #Guía De Cumplimiento en Seguridad, Salud Ocupacional y Medio Ambiente Para Contratistas y Proveedores# que forma parte de este Contrato.\nAsimismo, durante la prestación del Servicio debe cumplir, como mínimo, con lo siguiente:\n(i) Acceso para el personal. - El PROVEEDOR deberá cumplir con los requerimientos de CIMAL con relación al acceso de su personal al sitio de trabajo, incluyendo, pero sin estar limitado a, la presentación de documentos de calificación o acreditación, en original o copia certificada, los cuales deberán ser presentados por el PROVEEDOR previamente a cada ingreso o cuando CIMAL lo requiera.\n", 0, 'J');

$pdf->AddPage();
$pdf->SetXY(0, 5);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->MultiCell(0, 5, "CONDICIONES GENERALES DE COMPRA", 0, 'C');

$pdf->SetFont('helvetica', '', 7);
$pdf->MultiCell(90, 0, "(ii) Reemplazo de Personal. - El PROVEEDOR deberá reemplazar al personal que CIMAL considere no adecuado para la ejecución de los Servicios; todos los gastos y costos que resulten de dicho reemplazo serán asumidos por el PROVEEDOR.\n(iii) Personal del PROVEEDOR. - Sin perjuicio del derecho de CIMAL de monitorear la ejecución de los Servicios, el PROVEEDOR será responsable del personal que asigne para la prestación de los mismos. En particular, el PROVEEDOR será el único responsable de la organización, dirección, disciplina, supervisión y seguridad de su personal y el pago de todas las obligaciones contractuales o laborales asumidas con dicho personal. Dicho personal deberá regirse por toda la normativa y regulación aplicable al PROVEEDOR. \nAccidentes. - El PROVEEDOR será responsable por cualquier incidente, accidente o daños que sufra su personal o el personal de CIMAL o terceros como consecuencia de la ejecución de los\n(i) Servicios o de cualquier acto u omisión del PROVEEDOR o de su personal. El PROVEEDOR deberá, si lo requiere CIMAL, demostrar fehacientemente que cuenta con la cobertura de los seguros necesarios o recomendables según la legislación aplicable y las Buenas Prácticas de la Industria para cubrir riesgos relacionados con la ejecución del Contrato, incluyendo, pero no limitado a accidentes de trabajo. El PROVEEDOR renuncia a cualquier reclamo o acción contra CIMAL o sus aseguradores por cualquier accidente o daños sufridos por su personal, sus subcontratistas, sus representantes, agentes o terceros con relación a la ejecución del presente Contrato. El PROVEEDOR será responsable, tanto frente a CIMAL como a terceros, por todos los daños causados por su personal, sus subcontratistas, sus representantes, agentes o por cualquier maquinaria o equipo a cargo del PROVEEDOR con relación a la ejecución del Servicio. \n(ii) Permisos: El PROVEEDOR gestionará todos los permisos, licencias o autorizaciones que fuese necesarias para la ejecución del Servicio de acuerdo con la legislación aplicable. \n(iii) De ser el caso el PROVEEDOR preste servicios en las instalaciones de CIMAL, será aplicable la penalidad por SSOMA y BONO por SSOMA, regulada en #Guía De Cumplimiento en Seguridad, Salud Ocupacional y Medio Ambiente Para Contratistas y Proveedores#.\n", 0, 'J');

$pdf->SetFont('helvetica', 'B', 7);
$pdf->MultiCell(90, 0, "22. COMUNICACIONES ENTRE LAS PARTES", 0, '');

$pdf->SetFont('helvetica', '', 7);
$pdf->MultiCell(90, 0, "Toda comunicación relacionada con el día a día entre las Partes se realizará por correo electrónico. Dichas comunicaciones tendrán efectos contractuales. Todos los otros asuntos, incluyendo, pero no limitado a reclamos de las Partes, deberá ser realizada mediante documento escrito impreso y entregado en mano o por correo certificado, con constancia de cargo de recepción. El PROVEEDOR deberá indicar el número de la Orden en todos los documentos, correspondencia y correos electrónicos que intercambie con CIMAL.\nCLÁUSULA ADICIONAL\nEn caso las Partes hubiesen establecido en el Contrato que el PROVEEDOR está obligado a entregar cualquiera de los siguientes documentos a CIMAL: (i) Carta Fianza por Fiel Cumplimiento de las Obligaciones del Contrato y/o Carta Fianza por Adelanto y/o (ii) cualquier otra garantía financiera (carta fianza) en respaldo de obligaciones contractuales, dichos documentos deberán cumplir con lo siguiente: \n- Salvo pacto en contrario, el monto de la Carta Fianza por Fiel Cumplimiento de las Obligaciones del Contrato será el diez por ciento (10%) del íntegro de monto de la Orden, incluyendo el Impuesto General a las Ventas (IGV), y el monto de la Carta Fianza por Adelanto será el ciento por ciento (100%) del íntegro del adelanto otorgado al PROVEEDOR, incluyendo el IGV. \n- Deberán ser entregados como máximo dentro de los cinco (5) días hábiles de emitida la Orden o en el plazo que se establezca en la misma. \n- La vigencia de la Carta Fianza por Fiel Cumplimiento de las Obligaciones del Contrato deberá ser hasta los treinta (30) días calendario posteriores a la entrega de los Bienes o finalización de los Servicio. \n- Salvo que se estipule algo distinto en la Orden o en las Especificaciones, la vigencia de la Carta Fianza por Adelanto deberá ser hasta los treinta (30) días calendario posteriores a la aceptación de los Bienes y Servicios. \n- Las cartas fianza deberán ser solidarias, irrevocables, sin beneficio de excusión, incondicionales, de realización automática y emitidas por una entidad financiera de primer nivel, a satisfacción de CIMAL. No se aceptará pólizas de caución. \n- La carta fianza por adelanto garantizará el correcto uso de dicho adelanto, en tanto que la carta fianza de Fiel Cumplimiento garantizará el cumplimiento de todas las obligaciones del PROVEEDOR establecidas en el Contrato. \n- Las cartas fianza antes mencionadas estarán sujetas a la aceptación de CIMAL. \n- La contratación de las cartas fianza y su renovación es por cuenta exclusiva del PROVEEDOR. \n - El incumplimiento del PROVEEDOR con las obligaciones relativas a las cartas fianza es un incumplimiento grave que faculta a CIMAL a resolver el Contrato.\n", 0, 'J');

$pdf->Ln(4);

$y = 260;
$pdf->Line(30, $y, 70, $y);
$pdf->SetXY(34, 262);
$pdf->MultiCell(90, 0, "Aceptación del proveedor", 0, '');

/* 
$x=0; $y=0;
$pdf->cell(0, 0, 'ORDEN DE COMPRA', 1,1,'R',0,'',0);
$pdf->Cell(0, 10, '¡Hola, mundo! '. $rowOrden['razon'] .' Este es mi primer PDF creado con TCPDF.', 1, 1, 'C', 0, '', 0, false, 'T', 'M');
 */
// Salida del PDF
$pdf->Output('example.pdf', 'I');