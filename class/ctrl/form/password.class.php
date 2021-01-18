<?php
if (!defined('_BASE_DIR_')) exit();

class FormElem_Password extends FormElem {
	
	public function __construct($nome, $parent, $key=NULL, $obblig=false, $default=NULL) {
		parent::__construct($nome, $parent, $key, $obblig, $default);
		$this->persistente = false;
	}
	
	public function getValore() {
		return $this->getValoreRaw();
	}
	
	public function getTipo() {
		return FORMELEM_PASSWORD;
	}
}