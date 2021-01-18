<?php
session_start();
require_once 'config.inc.php';
Auth::logout();
go_home();