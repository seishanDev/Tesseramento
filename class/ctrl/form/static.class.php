<?php
if (!defined('_BASE_DIR_')) exit();

class FormElem_Static extends FormElem {

	public function __construct($nome, $parent, $key=NULL, $default=NULL) {
		parent::__construct($nome, $parent, $key, false, $default);
		$this->persistente = false;
		$this->disabled = true;
	}
	
	function setDisabilitato($val) {}

	function haValore() {
		return true;
	}
	
	function getValoreRaw() {
		return $this->default;
	}

	function getValore() {
		return $this->default;
	}
	
	function isValido() {
		$this->err = FORMERR_NO;
		return true;
	}
	
	public function getTipo() {
		return FORMELEM_STATIC;
	}
}