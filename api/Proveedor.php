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
	case 'filtrar': filtrar($db); break;
	case 'eliminar': eliminar($db); break;
	case 'eliminarRespuesta': eliminarRespuesta($db); break;
	case 'listarSinRepetir': listarSinRepetir($db); break;
	default: break;
}

function crear($db){
	$u = $_POST['registro'];
	$sql= $db->prepare("INSERT INTO `clientes`(
		`ruc`, `razon`, `direccion`, `celular`, `codigo`
		) VALUES (
		?, ?, ?, ?, ?
		);");
		if( $sql->execute([
			$u['ruc'], $u['razon'], $u['direccion'], $u['celular'], $u['codigo']
		]) )
			$id =  $db->lastInsertId();
		else $id = -1;
	
	echo json_encode( array('id' => $id));
}

function listar($db){	
	$filas = [];
	$sql=$db->prepare("SELECT o.orden as codigo, o.id as idOrden, p.* FROM `proveedor` p
	inner join orden_cabecera o on o.idProveedor = p.id
	where p.activo = 1
	order by razon asc;");
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

function filtrar($db){
	$filas = [];
	$filtro = "";

	if($_POST['filtro']['codigo']<>null) $filtro .=" and (p.ruc = '{$_POST['filtro']['codigo']}' or p.razon like '%{$_POST['filtro']['codigo']}%' or p.celular like '{$_POST['filtro']['codigo']}' )";

	$sql=$db->prepare("SELECT o.orden as codigo, o.id as idOrden, p.* FROM `proveedor` p
	inner join orden_cabecera o on o.idProveedor = p.id
	where p.activo = 1 {$filtro}
	order by razon asc;");
	$sql->execute();
	
	while($row = $sql->fetch(PDO::FETCH_ASSOC))
		$filas [] = $row;
	
	echo json_encode($filas);
}

function actualizar($db){
	$u = $_POST['servicio'];
	$sql = $db->prepare("UPDATE `proveedor` SET
	`ruc`=?,`razon`=?,`direccionDestino`=?,
	`celular`=? WHERE id = ?; ");
	if($sql->execute([
		$u['ruc'], $u['razon'], $u['direccion'],
		$u['celular'], $u['id']
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


function eliminar($db){
	if($_POST['nivel']==1){	
		$sql = $db->prepare("UPDATE `proveedor` SET
		activo = 0 WHERE id = ?; ");
		if($sql->execute([
			$_POST['id']
		])) echo 'ok';
		else echo 'error';
	}else echo 'sin permiso';
}


function eliminarRespuesta($db){
	$sql = $db->prepare("UPDATE `documentos` SET `activo` = '0' WHERE id = ?;");
	if($sql->execute([ $_POST['idRespuesta'] ])){
		echo 'ok';
	}else{
		echo 'error';
	}
}

function listarSinRepetir($db){	
	$filas = [];
	$sql=$db->prepare("SELECT trim(ruc) as ruc, trim(razon) as razon FROM `clientes`
	where ruc <>'' and razon<>'' and activo = 1
	group by trim(ruc), trim(razon)
	order by razon asc;");
	$sql->execute();
	while($row = $sql->fetch(PDO::FETCH_ASSOC))
		$filas [] = $row;
	
	echo json_encode($filas);
}