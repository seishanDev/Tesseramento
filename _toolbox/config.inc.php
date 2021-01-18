<?php
if (!isset($_GET['run'])) {
	echo 'Eseguire? <a href="?run">OK</a>';
	exit();
}

require_once '../config.inc.php';
check_login(UTENTE_ADMIN);
