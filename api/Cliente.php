<?php
error_reporting(E_ALL); ini_set("display_errors", 1);
$_POST = json_decode(file_get_contents('php://input'), true);

include 'conectInfocat.php';

switch ($_POST['pedir']) {
	case 'crear': crear($db); break;
	case 'actualizar': actualizar($db); break;
	case 'listar': listar($db); break;
	case 'listarID': listarID($db); break;
	case 'addUser': addUser($db); break;
	case 'eliminarRespuesta': eliminarRespuesta($db); break;
	default: break;
}

function crear($db){
	$u = $_POST['registro'];
	$sql= $db->prepare("INSERT INTO `clientes`(
		`ruc`, `razon`, `direccion`, `celular`
		) VALUES (
		?, ?, ?, ?
		);");
		if( $sql->execute([
			$u['ruc'], $u['razon'], $u['direccion'], $u['celular']
		]) )
			$id =  $db->lastInsertId();
		else $id = -1;
	
	echo json_encode( array('id' => $id));
}

function listar($db){
	$filas = [];
	$sql=$db->prepare("SELECT * FROM `clientes` order by id desc;");
	$sql->execute();
	while($row = $sql->fetch(PDO::FETCH_ASSOC))
		$filas [] = $row;
	
	echo json_encode($filas);
}

function listarID($db){
	$filas = [];
	$usuarios = []; $documentos = []; $respuestas = [];
	$sql=$db->prepare("SELECT * from clientes where id = ?;");
	$sql->execute([ $_POST['id'] ]);
	$row = $sql->fetch(PDO::FETCH_ASSOC);
		$filas = $row;

	$sqlUsuarios = $db->prepare("SELECT se.`id`, `idCliente`, se.`idUsuario`, se.`fecha`, concat( u.paterno,' ', u.materno, ' ', u.nombres) as nomUsuario FROM `clientes_empleados` se
	inner join usuarios u on u.idUsuario = se.idUsuario
	WHERE `idCliente` = ? and se.activo = 1 order by u.paterno;");
	$sqlUsuarios -> execute([ $_POST['id'] ]);
	while($rowUsuarios = $sqlUsuarios->fetch(PDO::FETCH_ASSOC))
		$usuarios [] = $rowUsuarios;

	$sqlDocumentos = $db->prepare("SELECT d.*, concat( u.paterno,' ', u.materno, ' ', u.nombres) as nomUsuario FROM `documentos` d
	inner join usuarios u on u.idUsuario = d.idUsuario
	where idServicio = ? and d.tipo=1 and d.activo=1 and d.grupo=7;");
	$sqlDocumentos->execute([ $_POST['id'] ]);
	while($rowDocumentos = $sqlDocumentos->fetch(PDO::FETCH_ASSOC))
		$documentos [] = $rowDocumentos;

	$sqlRespuestas = $db->prepare("SELECT d.*, concat( u.paterno,' ', u.materno, ' ', u.nombres) as nomUsuario FROM `documentos` d
	inner join usuarios u on u.idUsuario = d.idUsuario
	where idServicio = ? and d.tipo=2 and d.activo=1 and d.grupo=7 order by d.id asc;");
	$sqlRespuestas->execute([ $_POST['id'] ]);
	while($rowRespuestas = $sqlRespuestas->fetch(PDO::FETCH_ASSOC))
		$respuestas [] = $rowRespuestas;

	//$filas['documentos'] = [];
	echo json_encode(array(
		'cliente' => $filas,
		'usuarios' => $usuarios,
		'documentos' => $documentos,
		'respuestas' => $respuestas,
	));
}

function actualizar($db){
	$u = $_POST['registro'];
	$sql = $db->prepare("UPDATE `usuarios` SET
	`paterno`=?,`materno`=?,`nombres`=?,`tipo`= ?,
	`celular1`=?,`celular2`=?,`pais`=?,`departamento`=?,
	`provincia`=?,`distrito`=?,`direccion`=?,`correo`=?,`nivel`=? WHERE idUsuario = ?; ");
	if($sql->execute([
		$u['paterno'], $u['materno'], $u['nombres'], $u['tipo'], 
	$u['celular1'], $u['celular2'], $u['pais'], $u['departamento'], 
	$u['provincia'], $u['distrito'], $u['direccion'], $u['correo'], $u['nivel'], $u['idUsuario']
  ])) echo 'ok';
	else echo 'error';
}

function addUser($db){
	$sql = $db->prepare("INSERT INTO `clientes_empleados`(
	 `idCliente`, `idUsuario`, `quienAsigna`) VALUES (
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