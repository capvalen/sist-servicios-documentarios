<?php
error_reporting(E_ALL); ini_set("display_errors", 1);
$_POST = json_decode(file_get_contents('php://input'), true);

include 'conectInfocat.php';

switch ($_POST['pedir']) {
	case 'crear': crear($db); break;
	case 'listar': listar($db); break;
	case 'filtrar': filtrar($db); break;
	case 'listarMisServicios': listarMisServicios($db); break;
	case 'listarDetalle': listarDetalle($db); break;
	case 'cambiarEtapa': cambiarEtapa($db); break;
	case 'actualizarCabecera': actualizarCabecera($db); break;
	case 'quienAccede': quienAccede($db); break;
	case 'addUser': addUser($db); break;
	case 'borrarLector': borrarLector($db); break;
	case 'eliminarRespuesta': eliminarRespuesta($db); break;
	case 'eliminar': eliminar($db); break;
	default: break;
}

function crear($db){
	$s = $_POST['servicio'];
	$sql= $db->prepare("INSERT INTO `compra_venta`(
		`idUsuario`, `codigo`, `titulo`, `proceso`, `directo`, `idComprobante`, `categoria`,
		`ruc`, `razon`, `base`, `igv`, `total`, `moneda`,
		numeroDocumento, fechaDocumento
		) VALUES (
		?, ?, ?, 6, ?, ?, ?,
		?, ?, ?, ?, ?, ?,
		?, ?
		);");
		if( $sql->execute([
			$_POST['idUsuario'], $s['codigo'], $s['titulo'], $s['directo'], $s['idComprobante'],  $_POST['categoria'],
			$s['ruc'], $s['razon'], $s['base'], $s['igv'], $s['total'], $s['moneda'],
			$s['numeroDocumento'], $s['fechaDocumento']
		]) ){
			$id =  $db->lastInsertId();
			/* $sqlMov = $db->prepare("INSERT INTO movimientos (idUsuario,idServicio,idMovimiento,fecha) values(
			?,?, 1, now() );");
			$sqlMov->execute([ $_POST['idUsuario'], $id ]); */
		}
		else $id = -1;
	
	echo json_encode( array('id' => $id));
}

function listar($db){
	$filas = [];
	$sql=$db->prepare("SELECT s.*, concat( u.paterno,' ', u.materno, ' ', u.nombres) as nomUsuario FROM `servicios` s
	inner join usuarios u on u.idUsuario = s.idUsuario
	where s.activo = 1 order by s.id desc;");
	$sql->execute();
	while($row = $sql->fetch(PDO::FETCH_ASSOC))
		$filas [] = $row;
	
	echo json_encode($filas);
}

function listarMisServicios($db){
	$filas = [];
	//Analizar el ID que pide datos
	$sqlUsuario = $db->prepare("SELECT nivel from usuarios where idUsuario = ?; ");
	$sqlUsuario->execute([ $_POST['idUsuario'] ]);
	$rowUsuario = $sqlUsuario->fetch(PDO::FETCH_ASSOC);

	if($rowUsuario['nivel'] == -1 ){
		echo "[]"; return false;
	}
	else if($rowUsuario['nivel'] == 0 ){//Listar solo los servicios asignados al usuario
		$sql=$db->prepare("SELECT s.*, concat( u.paterno,' ', u.materno, ' ', u.nombres) as nomUsuario FROM `compra_venta` s
		inner join pagos_empleados se on se.idPago = s.id
		inner join usuarios u on u.idUsuario = s.idUsuario
		where se.activo = 1 and se.idUsuario = ? and categoria = ? order by s.id desc;");
		$sql->execute([ $_POST['idUsuario'],  $_POST['categoria'] ]);
	}
	else {// admin puede ver todo
		//if($rowUsuario['nivel'] == 1 )
		$sql=$db->prepare("SELECT s.*, concat( u.paterno,' ', u.materno, ' ', u.nombres) as nomUsuario FROM `compra_venta` s
		inner join usuarios u on u.idUsuario = s.idUsuario
		where s.activo = 1 and categoria = ? order by s.id desc limit 100;");
		$sql->execute([  $_POST['categoria']]);
	}
	
	while($row = $sql->fetch(PDO::FETCH_ASSOC))
		$filas [] = $row;
	
	echo json_encode($filas);
}

function filtrar($db){
	$filas = [];
	//Analizar el ID que pide datos
	$sqlUsuario = $db->prepare("SELECT nivel from usuarios where idUsuario = ?; ");
	$sqlUsuario->execute([ $_POST['idUsuario'] ]);
	$rowUsuario = $sqlUsuario->fetch(PDO::FETCH_ASSOC);

	$filtro = "";

	if($_POST['filtro']['estado']<>-1) $filtro =" and s.proceso = ".$_POST['filtro']['estado'] ;
	if($_POST['filtro']['directo']<>-1) $filtro =" and s.directo = ".$_POST['filtro']['directo'] ;
	if($_POST['filtro']['fecha']<>null) $filtro .=" and s.registro like '{$_POST['filtro']['fecha']} %'";
	if($_POST['filtro']['codigo']<>null) $filtro .=" and (s.codigo = '{$_POST['filtro']['codigo']}' or s.titulo like '%{$_POST['filtro']['codigo']}%' )";

	if($rowUsuario['nivel'] == 0 ){//Listar solo los servicios asignados al usuario
		$sql=$db->prepare("SELECT s.*, concat( u.paterno,' ', u.materno, ' ', u.nombres) as nomUsuario FROM `compra_venta` s
		inner join compra_venta_empleados se on se.idServicio = s.id
		inner join usuarios u on u.idUsuario = s.idUsuario
		where se.activo = 1 and se.idUsuario = ? {$filtro} and categoria = ?
		order by s.id desc;");
		$sql->execute([ $_POST['idUsuario'], $_POST['categoria'] ]);
	}
	if($rowUsuario['nivel'] == 1 ){// admin puede ver todo
		$sql=$db->prepare("SELECT s.*, concat( u.paterno,' ', u.materno, ' ', u.nombres) as nomUsuario FROM `compra_venta` s
		inner join usuarios u on u.idUsuario = s.idUsuario
		where s.activo = 1 {$filtro} and categoria = ?
		order by s.id;");
		$sql->execute([ $_POST['categoria'] ]);
	}else{
		echo "[]"; return false;
	}
	
	while($row = $sql->fetch(PDO::FETCH_ASSOC))
		$filas [] = $row;
	
	echo json_encode($filas);
}

function listarDetalle($db){
	$filas = [];
	$usuarios = []; $documentos = []; $respuestas = [];
	$sql=$db->prepare("SELECT s.*, concat( u.paterno,' ', u.materno, ' ', u.nombres) as nomUsuario FROM `compra_venta` s
	inner join usuarios u on u.idUsuario = s.idUsuario
	where s.activo = 1 and s.id = ?;");
	$sql->execute([ $_POST['id'] ]);
	$row = $sql->fetch(PDO::FETCH_ASSOC);
		$filas = $row;

	$sqlUsuarios = $db->prepare("SELECT se.`id`, `idPago`, se.`idUsuario`, se.`fecha`, concat( u.paterno,' ', u.materno, ' ', u.nombres) as nomUsuario FROM `compra_venta_empleados` se
	inner join usuarios u on u.idUsuario = se.idUsuario
	WHERE `idPago` = ? and se.activo = 1 order by u.paterno;");
	$sqlUsuarios -> execute([ $_POST['id'] ]);
	while($rowUsuarios = $sqlUsuarios->fetch(PDO::FETCH_ASSOC))
		$usuarios [] = $rowUsuarios;

	$sqlDocumentos = $db->prepare("SELECT d.*, concat( u.paterno,' ', u.materno, ' ', u.nombres) as nomUsuario FROM `documentos` d
	inner join usuarios u on u.idUsuario = d.idUsuario
	where idServicio = ? and d.tipo=1 and d.activo=1  and d.grupo=2;");
	$sqlDocumentos->execute([ $_POST['id'] ]);
	while($rowDocumentos = $sqlDocumentos->fetch(PDO::FETCH_ASSOC))
		$documentos [] = $rowDocumentos;

	$sqlRespuestas = $db->prepare("SELECT d.*, concat( u.paterno,' ', u.materno, ' ', u.nombres) as nomUsuario FROM `documentos` d
	inner join usuarios u on u.idUsuario = d.idUsuario
	where idServicio = ? and d.tipo=2 and d.activo=1 and d.grupo=2 order by d.id asc;");
	$sqlRespuestas->execute([ $_POST['id'] ]);
	while($rowRespuestas = $sqlRespuestas->fetch(PDO::FETCH_ASSOC))
		$respuestas [] = $rowRespuestas;

	//$filas['documentos'] = [];
	echo json_encode(array(
		'servicio' => $filas,
		'usuarios' => $usuarios,
		'documentos' => $documentos,
		'respuestas' => $respuestas,
	));
}

function actualizarCabecera($db){
	$s = $_POST['servicio'];
	$sql = $db->prepare("UPDATE `compra_venta` SET `codigo` = ?,titulo=?,  directo=?, idComprobante = ?,
	`ruc`=?, `razon`=?, `base`=?, `igv`=?, `total`=?, moneda=?,
	numeroDocumento=?, fechaDocumento=?
	WHERE `id` = ?;");
	if($sql->execute([ $s['codigo'], $s['titulo'], $s['directo'], $s['idComprobante'],
	$s['ruc'], $s['razon'], $s['base'], $s['igv'], $s['total'], $s['moneda'],
	$s['numeroDocumento'], $s['fechaDocumento'],
	$s['id'] ])){
		echo 'ok';
	}else{
		echo 'error';
	}
}

function cambiarEtapa($db){
	$sql = $db->prepare("UPDATE `compra_venta` SET `proceso` = ? WHERE `id` = ?;");
	if($sql->execute([ $_POST['etapa'], $_POST['id'] ])){
		/* $sqlMov = $db->prepare("INSERT INTO movimientos (idUsuario,idServicio,idMovimiento,fecha) values(
			?,?,?, now() );");
		$sqlMov->execute([ $_POST['idUsuario'], $_POST['id'], $_POST['etapa'] ]); */
		echo 'ok';
	}else{
		echo 'error';
	}
}

function quienAccede($db){
	$sql = $db->prepare("SELECT * FROM `pagos_empleados` where idPago = ? and idUsuario = ? and activo = 1");
	if($sql->execute([ $_POST['idServicio'], $_POST['idUsuario'] ])){
		$contar = $sql->rowCount();
		if( $contar == 0 ) echo 'noAccess';
		else{
			$_POST['id'] = $_POST['idServicio'];
			listarDetalle($db);
		}
	}else{
		echo 'error';
	}
}

function addUser($db){
	$sql = $db->prepare("INSERT INTO `compra_venta_empleados`(
	 `idPago`, `idUsuario`, `quienAsigna`) VALUES (
		?, ?, ?);");
	if($sql->execute([ $_POST['idServicio'], $_POST['idUsuario'], $_POST['quienAsigna'] ])){
		echo 'ok';
	}else{
		echo 'error';
	}
}

function eliminarRespuesta($db){
	$sql = $db->prepare("UPDATE `documentos` SET `activo` = '0' WHERE id = ?;");
	if($sql->execute([ $_POST['idRespuesta'] ])){
		echo 'ok';
	}else{
		echo 'error';
	}
}

function borrarLector($db){
	$sql = $db->prepare("UPDATE `pagos_empleados` SET `activo` = '0' WHERE `idPago` = ? and idUsuario = ? and activo = 1");
	if($sql->execute([ $_POST['idServicio'], $_POST['idUsuario'] ])){
		echo 'ok';
	}else{
		echo 'error';
	}
}

function eliminar($db){
	$sql = $db->prepare("UPDATE `compra_venta` SET `activo` = '0' WHERE `id` = ?;");
	if($sql->execute([ $_POST['id'] ])){
		echo 'ok';
	}else{
		echo 'error';
	}
}