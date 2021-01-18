<?php
if (!isset($_GET['id'])) exit();

require_once '../config.inc.php';
include_model('Provincia');
$res = array();
foreach (Provincia::listaRegione($_GET['id']) as $idp => $p) {
	$res[$idp] = $p->getNome();
}

echo json_encode($res);