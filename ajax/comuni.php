<?php
if (!isset($_GET['id'])) exit();

require_once '../config.inc.php';
include_model('Comune');
$res = array();
foreach (Comune::listaProvincia($_GET['id']) as $idc => $c) {
	$res[$idc] = $c->getNome();
}

echo json_encode($res);