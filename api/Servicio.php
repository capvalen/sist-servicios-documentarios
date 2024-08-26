<?php
error_reporting(E_ALL); ini_set("display_errors", 1);
$_POST = json_decode(file_get_contents('php://input'), true);

include 'conectInfocat.php';

switch ($_POST['pedir']) {
	case 'crear': crear($db); break;
	case 'listar': listar($db); break;
	case 'cambiarEtapa': cambiarEtapa($db); break;
	case 'listarDetalle': listarDetalle($db); break;
	case 'actualizarCabecera': actualizarCabecera($db); break;
	case 'quienAccede': quienAccede($db); break;
	case 'addUser': addUser($db); break;
	case 'borrarLector': borrarLector($db); break;
	case 'eliminar': eliminar($db); break;
	default: break;
}

function crear($db){
	$s = $_POST['servicio'];
	$sql= $db->prepare("INSERT INTO `servicios`(
		`idUsuario`, `codigo`, `titulo`
		) VALUES (
		?, ?, ?
		);");
		if( $sql->execute([
			$_POST['idUsuario'], $s['codigo'], $s['titulo']
		]) ){
			$id =  $db->lastInsertId();
			$sqlMov = $db->prepare("INSERT INTO movimientos (idUsuario,idServicio,idMovimiento,fecha) values(
			?,?, 1, now() );");
			$sqlMov->execute([ $_POST['idUsuario'], $id ]);
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

function listarDetalle($db){
	$filas = [];
	$usuarios = [];
	$sql=$db->prepare("SELECT s.*, concat( u.paterno,' ', u.materno, ' ', u.nombres) as nomUsuario FROM `servicios` s
	inner join usuarios u on u.idUsuario = s.idUsuario
	where s.activo = 1 and s.id = ?;");
	$sql->execute([ $_POST['id'] ]);
	$row = $sql->fetch(PDO::FETCH_ASSOC);
		$filas = $row;

	$sqlUsuarios = $db->prepare("SELECT se.`id`, `idServicio`, se.`idUsuario`, se.`fecha`, concat( u.paterno,' ', u.materno, ' ', u.nombres) as nomUsuario FROM `servicios_empleados` se
	inner join usuarios u on u.idUsuario = se.idUsuario
	WHERE `idServicio` = ? and se.activo = 1 order by u.paterno;");
	$sqlUsuarios -> execute([ $_POST['id'] ]);
	while($rowUsuarios = $sqlUsuarios->fetch(PDO::FETCH_ASSOC))
		$usuarios [] = $rowUsuarios;

	//$filas['documentos'] = [];
	echo json_encode(array(
		'servicio' => $filas,
		'usuarios' => $usuarios
	));
}

function actualizarCabecera($db){
	$s = $_POST['servicio'];
	$sql = $db->prepare("UPDATE `servicios` SET `codigo` = ?, titulo=? WHERE `id` = ?;");
	if($sql->execute([ $s['codigo'], $s['titulo'], $s['id'] ])){
		echo 'ok';
	}else{
		echo 'error';
	}
}

function cambiarEtapa($db){
	$sql = $db->prepare("UPDATE `servicios` SET `proceso` = ? WHERE `id` = ?;");
	if($sql->execute([ $_POST['etapa'], $_POST['id'] ])){
		$sqlMov = $db->prepare("INSERT INTO movimientos (idUsuario,idServicio,idMovimiento,fecha) values(
			?,?,?, now() );");
			$sqlMov->execute([ $_POST['idUsuario'], $_POST['id'], $_POST['etapa'] ]);
		echo 'ok';
	}else{
		echo 'error';
	}
}

function quienAccede($db){
	$sql = $db->prepare("SELECT * FROM `servicios_empleados` where idServicio = ? and idUsuario = ? and activo = 1");
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
	$sql = $db->prepare("INSERT INTO `servicios_empleados`(
	 `idServicio`, `idUsuario`, `quienAsigna`) VALUES (
		?, ?, ?);");
	if($sql->execute([ $_POST['idServicio'], $_POST['idUsuario'], $_POST['quienAsigna'] ])){
		echo 'ok';
	}else{
		echo 'error';
	}
}

function borrarLector($db){
	$sql = $db->prepare("UPDATE `servicios_empleados` SET `activo` = '0' WHERE `idServicio` = ? and idUsuario = ? and activo = 1");
	if($sql->execute([ $_POST['idServicio'], $_POST['idUsuario'] ])){
		echo 'ok';
	}else{
		echo 'error';
	}
}

function eliminar($db){
	$sql = $db->prepare("UPDATE `servicios` SET `activo` = '0' WHERE `id` = ?;");
	if($sql->execute([ $_POST['id'] ])){
		echo 'ok';
	}else{
		echo 'error';
	}
}