<?php
session_start();
require_once 'config.inc.php';
include_view('Login');

$tmpl = get_template();
$tmpl->setTitolo("Login");
$tmpl->addBody(
		'<div class="row-fluid" style="margin-top:50px;"><div class="span4 offset2">',
		new Login(),
		'</div><div class="span1"></div><div class="span5">',
		'<a href="richiestaaff.php" class="btn btn-large">Nuova affiliazione</a>',
		'</div></div>');
$tmpl->stampa();