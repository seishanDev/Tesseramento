<?php 

/**
 * percorso reale della cartella base
 */
define('_BASE_DIR_', $_SERVER['DOCUMENT_ROOT']._PATH_ROOT_);
/**
 * percorso reale della cartella delle classi
*/
define('_CLASS_DIR_', _BASE_DIR_.'class/');
/**
 * percorso reale della cartella delle view
 */
define('_VIEW_DIR_', _CLASS_DIR_.'view/');

/**
 * Url per l'inclusione di Bootstrap
 */
define('_BOOTSTRAP_URL_', _PATH_ROOT_.'bootstrap/');
/**
 * componenti di JQuery-UI
 */
define('_JQUERYUI_JS_',_JQUERYUI_URL_.'/jquery-ui.js');
define('_JQUERYUI_THEME_',_JQUERYUI_URL_.'/themes/smoothness/jquery-ui.css');

/**
 * chiave di $GLOBALS per memorizzare il template da utilizzare
 */
define('TEMPLATE_KEY', 'tess_template');
define('TEMPLATE_OBJ', 'tess_template_object');

/**
 * Mese a partire dal quale inizia il periodo di rinnovo 
 */
define('MESE_RINNOVO',9); // da modificare ogni anno prima di settembre, affinchè barbara possa operare per scaricare i tesserati

/**
 * nessun utente loggato
 */
define('UTENTE_NO', 0);
/**
 * utente di tipo amministrativo
 */
define('UTENTE_ADMIN', 1);
/**
 * utente di tipo società
 */
define('UTENTE_SOC', 2);
/**
 * utente di tipo segreteria
 */
define('UTENTE_SEGR', 3);

define('E_DEBUG', E_USER_NOTICE);
define('E_INFO', E_NOTICE);

include_class('Auth');

function set_template($file) {
	if (isset($GLOBALS[TEMPLATE_KEY]) && $file !== $GLOBALS[TEMPLATE_KEY])
		unset($GLOBALS[TEMPLATE_OBJ]);
	if ($file === NULL)
		unset($GLOBALS[TEMPLATE_KEY]);
	else
		$GLOBALS[TEMPLATE_KEY] = $file;
}

/**
 * @return Template
 */
function get_template() {
	if (!isset($GLOBALS[TEMPLATE_OBJ])) {
		if (isset($GLOBALS[TEMPLATE_KEY])) 
			$file = $GLOBALS[TEMPLATE_KEY];
		else
			$file = 'default';
		require_once(_VIEW_DIR_."template/$file.view.php");
		$class =  TEMPLATE_CLASS;
		$GLOBALS[TEMPLATE_OBJ] = new $class();
	}
	return $GLOBALS[TEMPLATE_OBJ];
}

/*-------------------------------- INCLUDE ----------------------------------*/
function include_class() {
	foreach(func_get_args() as $file) {
		require_once(_CLASS_DIR_."$file.php");
	}
}

function include_model() {
	foreach(func_get_args() as $file) {
		require_once(_CLASS_DIR_."model/$file.php");
	}
}

function include_view() {
	foreach(func_get_args() as $file) {
		require_once(_VIEW_DIR_."$file.view.php");
	}
}

function include_controller() {
	foreach(func_get_args() as $file) {
		require_once(_CLASS_DIR_."ctrl/$file.class.php");
	}
}

function include_form() {
	foreach(func_get_args() as $file) {
		require_once(_CLASS_DIR_."ctrl/form/$file.class.php");
	}
}

function include_formview() {
	foreach(func_get_args() as $file) {
		require_once(_VIEW_DIR_."form/$file.view.php");
	}
}

/* ------------------------------- REDIRECT ----------------------------------*/
/**
 * Redireziona verso una pagina del sito e termina lo script
 * @param string $pagina la pagina in cui andare. 
 * Pecorso relativo alla root del tesseramento
 * @param string $prot [opz] http o https
 */
function redirect($pagina, $prot='http'){
	header("Location: $prot://$_SERVER[HTTP_HOST]"._PATH_ROOT_.$pagina);
	exit();
}

/**
 * Ricarica la pagina attuale
 */
function ricarica() {
	header("Location: http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
	exit();
}

/**
 * Termina lo script attuale e redireziona verso la pagina di login 
 */
function go_login() {
	include_class('Log');
	Log::debug('go_login',array('session'=>$_SESSION, 'cookie'=>$_COOKIE, 'server'=>$_SERVER));
	redirect('login.php');
} 

/**
 * Termina lo script attuale e redireziona verso l'homepage dell'utente
 */
function go_home($return=false) {
	switch (Auth::getTipoUtente()) {
		case UTENTE_SOC:
			$pag = 'soc/';
			break;
		case UTENTE_SEGR:
			$pag = 'segr/';
			break;
		case UTENTE_ADMIN:
			$pag = 'admin/';
			break;
		default:
			$pag = 'index.php';
			break;
	}
	if ($return) 
		return _PATH_ROOT_.$pag;
	else
		redirect($pag);
}


/* ------------------------------- CONTROLLO ACCESSI ----------------------------------*/

function check_login($tipo = NULL) {
	$tu = Auth::getTipoUtente();
	
	if($tu == UTENTE_NO) {go_login();}
	else if($tipo !== NULL && $tipo != $tu) {go_home();}
}

function check_get($key) {
	if(!isset($_GET[$key])) {go_home();}
}



/**
 * Indica se la data specificata è in periodo di rinnovo
 * @param Data $data [opz] se NULL allora utilizza la data di oggi
 */
function in_rinnovo($data=NULL) {
	if ($data === NULL) 
		$m = date('n');
	else 
		$m = $data->getMese();
	return ($m >= MESE_RINNOVO);
}