<?php
error_reporting(E_ALL); ini_set("display_errors", 1);
require("conectInfocat.php");
$data = $_POST;

$sql=$db->prepare("SELECT idUsuario, paterno, materno, nombres, nivel FROM usuarios where dni = ? and clave = MD5(?) and activo = 1;");
if($sql->execute([
	$data['usuario'], $data['clave']
])){
	$filas = $sql->rowCount();
	//echo 'filas: '. $filas;
	if($filas > 0){
		$row = $sql->fetch(PDO::FETCH_ASSOC);
		
		echo json_encode(array('mensaje' => 'ok', 'usuario'=> $row));

	}else
		echo json_encode(array('mensaje' => 'error1'));
}else
	echo json_encode(array('mensaje' => 'error2'));