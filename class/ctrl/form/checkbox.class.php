<?php
if (!defined('_BASE_DIR_')) exit();

class FormElem_Check extends FormElem {

	/**
	 * @param string $nome
	 * @param Form $parent
	 * @param boolean $default
	 */
	public function __construct($nome, $parent, $key=NULL, $default=NULL) {
		parent::__construct($nome, $parent, $key, false, $default);
	}

	public function getValore() {
		return ($this->getValoreRaw() !== NULL);
	}

	public function getTipo() {
		return FORMELEM_CHECK;
	}
}