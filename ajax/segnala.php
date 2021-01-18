<?php
if (!isset($_POST['pagina']) || !isset($_POST['descrizione'])) exit('0');

define('EMAIL_DEST','supporto@fiamsport.it');

session_start();
require_once('../config.inc.php');
include_model('Segnalazione');

if (isset($_POST['email'])) {
	$email = trim($_POST['email']);
	Auth::setEmailSegnalazione($email);
} else
	$email = Auth::getEmailSegnalazione();

$s = Segnalazione::crea($_POST['pagina'], $_POST['descrizione'], $email);
if (!$s->salva()){
	$log = $_POST;
	$u =  Auth::getUtente();
	if ($u !== NULL)
		$log['idutente'] = $u->getId();
	include_class('Log');
	Log::warning('segnalazione non salvata',$log);
}

if (_LOCALHOST_) exit('1');

$ut = Auth::getUtente();
$nomeFrom = '';
$nomesoc = '';
if ($ut === NULL) {
	$bodyemail = '';
	$idu = 'nessuno';
	$tipo = '';
	$user = '';
} else {
	if ($ut->getTipo() == UTENTE_SOC) {
		$soc = $ut->getSocieta();
		$nome = $soc->getNomeBreve();
		$nl = $soc->getNome();
		$nomesoc = "$nome ($nl)";
		if ($ut->getTipoReale() == UTENTE_SOC)
			$nomeFrom = $nome;
	} 
	$bodyemail = $ut->getEmail();
	if ($email === NULL) {
		$email = $ut->getEmail();
	} else if ($bodyemail != $email) {
		$bodyemail = "$email $bodyemail";
	}
	$idu = $ut->getId();
	$tipo = $ut->getNomeTipoReale();
	$user = $ut->getUsername();
}

if ($email === NULL) $email = EMAIL_DEST;
if ($nomeFrom == '') {
	if ($user != '')
		$nomeFrom = $user;
	else
		$nomeFrom = $email;
}

$body = "ID Utente: $idu\r\nUsername: $user\r\nSocietà: $nomesoc\r\nEmail: $bodyemail\r\nTipo: $tipo";
$body .= "\r\n\r\nPagina: $_POST[pagina]\r\nBrowser: $_SERVER[HTTP_USER_AGENT]\r\n\r\n$_POST[descrizione]";

require_once(_MAILER_INCLUDE_);
$mail = new PHPMailer();
//$mail->IsSMTP();                    // attiva l'invio tramiteSMTP
//$mail->Host= '$SMTP'; // indirizzo smtp
$mail->CharSet = 'UTF-8';
$mail->From = $email;
$mail->FromName = $nomeFrom;
$mail->AddAddress(EMAIL_DEST);
$mail->AddBCC('flavio.debene@gmail.com'); //DEBUG da eliminare se funziona l'email fiamsport
$mail->IsHTML(false);
$mail->Subject  =  '[Tesseramento] SEGNALAZIONE '.$s->getId();
$mail->Body     =  $body;

if (!$mail->Send()) {
	include_class('Log');
	Log::warning('email segnalazione non inviata',
		array('id'=>$s->getId(), 'err'=>$mail->ErrorInfo, 'obj'=>$mail));
}

exit('1');

?>