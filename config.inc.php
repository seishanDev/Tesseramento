<?php
error_reporting(0);

/**
 * impostare a true se si lavora in locale, false per il server
 */
define("_LOCALHOST_", false);
/**
 * percorso relativo della cartella base, iniziare e finire con /
 */
define("_PATH_ROOT_", '/tesseramento/');

/**
 * percorso relativo del file di configurazione del database
 */
define("_DB_CONFIG_", 'connection.inc.php');

/**
 * Url per l'inclusione di JQuery
 */
define('_JQUERY_URL_','http://code.jquery.com/jquery-1.10.1.min.js');

/**
 * Url per l'inclusione di JQuery-UI
*/
define('_JQUERYUI_URL_',_PATH_ROOT_.'js/jqueryui');

/**
 * File da includere per PHPMailer
 */
define('_MAILER_INCLUDE_', $_SERVER['DOCUMENT_ROOT'].'/PHPMailer/class.phpmailer.php');


require_once 'common.inc.php';

define('LOG_LEVEL', E_DEBUG);