<?php
/* Net	*/
$username="root";
$password="*123456*";
$datab= "documentario";

//Con Objetos:
try {
	$db = new PDO (
		'mysql:host=localhost;
		dbname='.$datab,
		$username,
		$password,
		array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")
	);
} catch (Exception $e) {
	echo "Problema con la conexion: ".$e->getMessage();
}

?>