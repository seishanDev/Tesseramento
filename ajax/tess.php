<?php
if(!isset($_GET['idt'])) exit();

session_start();
require_once '../config.inc.php';

switch (Auth::getTipoUtente()) {
	case UTENTE_SOC:
		$ids = Auth::getUtente()->getIDSocieta();
		break;
	case UTENTE_ADMIN:
	case UTENTE_SEGR:
		$ids = NULL;
		break;
	default:
		exit();
}

include_view('DettagliTesserato');

$view = new DettagliTesserato($_GET['idt'], $ids);
$view->stampa();
echo '<script type="text/javascript">';
$view->stampaJsOnload();
echo '</script>';