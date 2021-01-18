<?php
session_start();
require_once 'config.inc.php';

if (Auth::getUtente()===NULL)
	go_login();
else
	go_home();
