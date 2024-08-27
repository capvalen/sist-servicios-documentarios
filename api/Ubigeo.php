<?php
error_reporting(E_ALL); ini_set("display_errors", 1);
$_POST = json_decode(file_get_contents('php://input'), true);

include 'conectInfocat.php';

switch ($_POST['pedir']) {
	case 'listar': listar($db); break;
	default: break;
}

function listar($db){
	$departamentos = [];
	$sqlDepartamentos= $db->prepare("SELECT * FROM `ubigeo_peru_departments`;");
	$sqlDepartamentos->execute();
	while($rowDepartamentos = $sqlDepartamentos->fetch(PDO::FETCH_ASSOC))
		$departamentos [] = $rowDepartamentos;
	
	$provincias = [];
	$sqlProvincias= $db->prepare("SELECT * FROM `ubigeo_peru_provinces`;");
	$sqlProvincias->execute();
	while($rowProvincias = $sqlProvincias->fetch(PDO::FETCH_ASSOC))
		$provincias [] = $rowProvincias;

	$distritos = [];
	$sqlDistritos= $db->prepare("SELECT * FROM `ubigeo_peru_districts`;");
	$sqlDistritos->execute();
	while($rowDistritos = $sqlDistritos->fetch(PDO::FETCH_ASSOC))
		$distritos [] = $rowDistritos;
	
		echo json_encode(
		array(
			'departamentos' => $departamentos,
			'provincias' => $provincias,
			'distritos' => $distritos
		));
}