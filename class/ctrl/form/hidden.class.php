<?php
if (!defined('_BASE_DIR_')) exit();

class FormElem_Hidden extends FormElem {

	public function getValore() {
		return $this->getValoreRaw();
	}

	public function getTipo() {
		return FORMELEM_HIDDEN;
	}
}