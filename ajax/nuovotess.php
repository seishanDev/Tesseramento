<?php
/**
 * @param int $stato
 * @param Tesserato $tes
 * @param array $err
 */
function stampaOutput($stato, $tes=NULL, $err=array()) {
	$res['idform'] = $_POST['nuovo_tesserato_id'];
	$res['stato'] = $stato;
	$res['err'] = $err;
	if ($tes === NULL) {
		$res['idtess'] = '';
		$res['nome'] = '';
	} else {
		$res['idtess'] = $tes->getId();
		$res['nome'] = $tes->getCognome().' '.$tes->getNome();
	}
	echo json_encode($res);
	exit();
}

function callback($tes) {
	stampaOutput(1, $tes);
}

session_start();
require_once '../config.inc.php';

switch (Auth::getTipoUtente()) {
	case UTENTE_SOC:
		$ids = Auth::getUtente()->getIDSocieta();
		break;
	//TODO case admin
// 	case UTENTE_ADMIN:
// 		$ids = $_GET['ids'];
// 		break;
	default:
		stampaOutput(-1);
}

include_controller('NuovoTesserato');
$ctrl = new NuovoTesseratoCtrl($ids, 'callback');
stampaOutput(0, NULL, $ctrl->getErrori());
