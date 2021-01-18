<?php
if (!defined("_BASE_DIR_")) exit();
include_form('Form','password');

define('FORM_USER','username');
define('FORM_PSW','password');

class LoginCtrl {
	private $err = false;
	private $form;
	
	function __construct(){
		$this->form = new Form('login', false);
		$user = new FormElem(FORM_USER, $this->form, NULL, true);
		$psw = new FormElem_Password(FORM_PSW, $this->form, NULL, true);
		$this->form->addSubmit('Login');
		if($this->form->isInviato() && $this->form->isValido()){
			$r = Auth::login($user->getValore(), $psw->getValore());
			 	
			if($r == Auth::OK)
				go_home();
			else 
				$this->err = true;
		} 
	}
	
	function getForm() {
		return $this->form;
	}
	
	function getErrore() {
		return $this->err;
	}
}