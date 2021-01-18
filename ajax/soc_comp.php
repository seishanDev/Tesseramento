<?php
if (!isset($_GET['id'])) exit();

require_once '../config.inc.php';
include_model('Societa');
$res = array();
$soc = Societa::fromId($_GET['id']);
foreach (Societa::listaCompleta($soc->getIdFederazione()) as $id_s=>$s) {
	$res[$id_s] = $s->getNomeBreve();
}

echo json_encode($res);