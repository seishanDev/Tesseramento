<?php
session_start();
require_once 'config.inc.php';
include_view('NuovaRichiestaAff');

$tmpl = get_template();
$tmpl->setTitolo("Richiesta affiliazione");
$tmpl->addBody(new NuovaRichiestaAff());
$tmpl->stampa();