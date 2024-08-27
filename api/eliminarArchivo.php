<?php
error_reporting(E_ALL); ini_set("display_errors", 1);
require("conectInfocat.php");

$uploadDir = __DIR__. "/../subidas/";
$_POST = json_decode(file_get_contents('php://input'), true);

$archivo = $_POST['ruta'];
$ruta = $uploadDir. $archivo;
$nuevaRuta = __DIR__. "/../subidas/eliminados/".$archivo;

if (rename($ruta, $nuevaRuta)) {
	//echo "El archivo ha sido subido correctamente con el nombre: " . $uniqueFilename;

	// ------- Guardar el registro en la DB
	
	
	$sql = $db->prepare("UPDATE `documentos` SET activo = 0 where id = ?");
	$sql->execute([ $_POST['idAdjunto'] ]);
	echo json_encode( array( 'estado' => 'eliminado' ));
	//echo 'ok';
	
} else {
	$error = error_get_last();
	echo json_encode( array('estado' => "error ". $error['message']));
}
