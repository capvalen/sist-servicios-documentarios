<?php
error_reporting(E_ALL); ini_set("display_errors", 1);
$_POST = json_decode(file_get_contents('php://input'), true);

include 'conectInfocat.php';

switch ($_POST['pedir']) {
	case 'crear': crear($db); break;
	case 'actualizar': actualizar($db); break;
	case 'listar': listar($db); break;
	case 'listarID': listarID($db); break;
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
	$sql=$db->prepare("SELECT count(s.id) as cantDocs, u.* FROM `usuarios` u
	left join servicios s on s.idUsuario = u.idUsuario WHERE u.activo = 1
	and u.idUsuario = ?;");
	$sql->execute([ $_POST['id'] ]);
	$row = $sql->fetch(PDO::FETCH_ASSOC);
	$filas = $row;
	
	echo json_encode($filas);
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