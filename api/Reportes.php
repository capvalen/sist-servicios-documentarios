<?php
error_reporting(E_ALL); ini_set("display_errors", 1);

include 'conectInfocat.php';
$_POST = json_decode(file_get_contents('php://input'), true);

switch ($_POST['filtro']['tipo']) {
	case '1':reporteCompras($db, 1); break;
	case '2': case '3': case '4':
		reporteCompras($db, $_POST['filtro']['tipo']); break;
}

function reporteCompras($db, $categoria){
	$filas = [];
	$sql = $db->prepare("SELECT c.*, concat( u.paterno,' ', u.materno, ' ', u.nombres) as nomUsuario, p.proceso as nomProceso FROM `compra_venta` c
	inner join usuarios u on u.idUsuario = c.idUsuario
	inner join procesos p on p.id = c.proceso
	where categoria = {$categoria} and c.activo = 1 and date_format(c.registro, '%Y-%m-%d') between ? and ?
	;");
	$sql->execute([ $_POST['filtro']['inicio'], $_POST['filtro']['fin'] ]);
	while($row = $sql->fetch(PDO::FETCH_ASSOC) )
		$filas [] = $row;

	echo json_encode($filas);
}