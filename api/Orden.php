<?php
error_reporting(E_ALL); ini_set("display_errors", 1);
$_POST = json_decode(file_get_contents('php://input'), true);

include 'conectInfocat.php';

switch ($_POST['pedir']) {
	case 'crear': crear($db); break;
	case 'actualizar': actualizar($db); break;
	case 'listarID': listarID($db); break;
	case 'crearProductoVacio': crearProductoVacio($db); break;
	case 'eliminarProducto': eliminarProducto($db); break;
	default: break;
}

function listarID($db){
	$productos = [];

	$sqlProveedor = $db->prepare("SELECT p.* 
	from proveedor p inner join orden_cabecera oc
	on oc.idProveedor = p.id
	where oc.id = ?;");
	$sqlProveedor->execute([ $_POST['idOrden'] ]);
	$rowProveedor = $sqlProveedor->fetch(PDO::FETCH_ASSOC);

	$sqlOrden = $db->prepare("SELECT * FROM orden_cabecera
	where id = ?;");
	$sqlOrden->execute([ $_POST['idOrden'] ]);
	$rowOrden = $sqlOrden->fetch(PDO::FETCH_ASSOC);

	$sqlDetalle = $db->prepare("SELECT * FROM orden_detalle
	where idOrden = ? and activo = 1;");
	$sqlDetalle->execute([ $_POST['idOrden'] ]);
	while($rowlDetalle = $sqlDetalle->fetch(PDO::FETCH_ASSOC))
		$productos[] = $rowlDetalle;
	
		echo json_encode(array(
			'proveedor' => $rowProveedor,
			'orden' => $rowOrden,
			'productos' => $productos
		));
}

function crear($db){
	$p = $_POST['proveedor'];
	$o = $_POST['orden'];
	$sql= $db->prepare("INSERT INTO `proveedor`(
		`ruc`, `razon`, `direccionDestino`, `celular`, `zip`,`atencion`,
		`correo`, `emision`, `diasPago`, `referencia`, `moneda`, `incoterm`
		) VALUES (
		?, ?, ?, ?, ?, ?, 
		?, ?, ?, ?, ?, ?
		);");
		if( $sql->execute([
			$p['ruc'], $p['razon'], $p['direccionDestino'], $p['celular'], $p['zip'], $p['atencion'],
			$p['correo'], $p['emision'], $p['diasPago'], $p['referencia'], $p['moneda'], $p['incoterm']
		]) )
			$idProveedor =  $db->lastInsertId();
		else $idProveedor = -1;
	
	if($idProveedor>0){
		$sqlOrden = $db->prepare("INSERT INTO orden_cabecera(
		 `orden`, `ruc`, `razon`, `direccion`, `celular`, `ciudad`,
		 `pais`, `casilla`, `enviar`, `direccionDestino`, `ciudadDestino`, `paisDestino`,
		 `almacen`, `lugar`, `direccionEntrega`, `contacto`, `observaciones`, `telefonoComprador`,
		 `correoComprador`, `aprobador1`, `aprobador2`, `aprobador3`, `idProveedor`
		) VALUES (
			?, ?, ?, ?, ?, ?,
			?, ?, ?, ?, ?, ?,
			?, ?, ?, ?, ?, ?,
			?, ?, ?, ?, ?
		);");
		if($sqlOrden->execute([
			$o['orden'], $o['ruc'], $o['razon'], $o['direccion'], $o['celular'], $o['ciudad'],
			$o['pais'], $o['casilla'], $o['enviar'], $o['direccionDestino'], $o['ciudadDestino'], $o['paisDestino'],
			$o['almacen'], $o['lugar'], $o['direccionEntrega'], $o['contacto'], $o['observaciones'], $o['telefonoComprador'],
			$o['correoComprador'], $o['aprobador1'], $o['aprobador2'], $o['aprobador3'], $idProveedor
		])){
			$idOrden = $db->lastInsertId();
		}else $idOrden = -1;

		if($idOrden>1){
			foreach ($_POST['productos'] as $pro) {
				$sqlProducto = $db->prepare("INSERT INTO orden_detalle (
				`idOrden`, `solped`, `pos`, `codigo`, `descripcion`, `observaciones`,
				`fecha`, `cantidad`, `medida`, `precioUnitario`
				) VALUES (
					?, ?, ?, ?, ?, ?,
					?, ?, ?, ?
				);");
				$sqlProducto->execute([
					$idOrden, $pro['solped'], $pro['pos'], $pro['codigo'], $pro['descripcion'], $pro['observaciones'],
					$pro['fecha'], $pro['cantidad'], $pro['medida'], $pro['precioUnitario']
				]);
			}

		}
	}
	echo json_encode( array('idProveedor' => $idProveedor, 'idOrden'=> $idOrden, 'productos'=> count($_POST['productos'])));
}

function actualizar($db){
	$p = $_POST['proveedor'];
	$o = $_POST['orden'];
	$productos =[];

	$sql = $db->prepare("UPDATE `proveedor` SET 
		`ruc`=?, `razon`=?, `direccionDestino`=?, `celular`=?, `zip`=?,`atencion`=?,
		`correo`=?, `emision`=?, `diasPago`=?, `referencia`=?, `moneda`=?, `incoterm`=?
	 WHERE `id` = ?;");
	if($sql->execute([
		$p['ruc'], $p['razon'], $p['direccionDestino'], $p['celular'], $p['zip'], $p['atencion'],
		$p['correo'], $p['emision'], $p['diasPago'], $p['referencia'], $p['moneda'], $p['incoterm'],
		$p['id']
	])){
		
		$sqlOrden = $db->prepare("UPDATE orden_cabecera SET
			`orden`=?, `ruc`=?, `razon`=?, `direccion`=?, `celular`=?, `ciudad`=?,
			`pais`=?, `casilla`=?, `enviar`=?, `direccionDestino`=?, `ciudadDestino`=?, `paisDestino`=?,
			`almacen`=?, `lugar`=?, `direccionEntrega`=?, `contacto`=?, `observaciones`=?, `telefonoComprador`=?,
			`correoComprador`=?, `aprobador1`=?, `aprobador2`=?, `aprobador3`=? WHERE `id` = ?;");
		if($sqlOrden->execute([
			$o['orden'], $o['ruc'], $o['razon'], $o['direccion'], $o['celular'], $o['ciudad'],
			$o['pais'], $o['casilla'], $o['enviar'], $o['direccionDestino'], $o['ciudadDestino'], $o['paisDestino'],
			$o['almacen'], $o['lugar'], $o['direccionEntrega'], $o['contacto'], $o['observaciones'], $o['telefonoComprador'],
			$o['correoComprador'], $o['aprobador1'], $o['aprobador2'], $o['aprobador3'], $o['id']
		]));
		
		echo 'ok';
	}else{
		echo 'error';
	}
}

function crearProductoVacio($db){
	$sqlProducto = $db->prepare("INSERT INTO orden_detalle (
		`idOrden`, `solped`, `pos`, `codigo`, `descripcion`, `observaciones`,
		`fecha`, `cantidad`, `medida`, `precioUnitario`
		) VALUES (
			?, '', '', '', '', '',
			null, 1, '', 0
		);");
		$sqlProducto->execute([
			$_POST['idOrden']
		]);
		$idProducto = $db->lastInsertId();
		echo json_encode( array('idProducto' => $idProducto) );
}

function eliminarProducto($db){
	$sqlOrden = $db->prepare("UPDATE orden_detalle SET
			`activo`=0 WHERE `id` = ?;");
		if($sqlOrden->execute([
			$_POST['idProducto']
		])){
			echo 'ok';
		}else{
			echo 'error';
		}
}