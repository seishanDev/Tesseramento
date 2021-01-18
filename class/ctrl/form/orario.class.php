<?php
if (!defined('_BASE_DIR_')) exit();

class FormElem_Orario extends FormElem {
	/**
	 * @var FormElem_Data
	 */
	private $data = NULL;
	
	/**
	 * Restituisce l'elemento contenente la data
	 * @return FormElem_Data|NULL
	 */
	public function getData() {
		return $this->data;
	}
	
	/**
	 * Imposta l'elemento contenente la data
	 * @param FormElem_Data $el
	 */
	public function setData($el) {
		$this->data = $el;		
	}
	
	/**
	 * Indica se è stato impostato un elemento data
	 * @return boolean
	 */
	public function haData() {
		return $this->data !== NULL;
	}
		
	function getValore() {
		if ($this->data === NULL) {
			$d = NULL;
		} else {
			$d = $this->data->getValore();
			//data non valida
			if ($d === NULL) return NULL;
		}
		return TimestampUtil::get()->parse($this->getValoreRaw(), $d);
	}
	
	function isValido() {
		$this->err = FORMERR_NO;
		$raw = $this->getValoreRaw();
		if ($raw === NULL || trim($raw) == '') {
			if($this->obblig)
			{
				$this->err = FORMERR_OBBLIG;
				return false;
			}
			else
				return true;
		}
		if ($this->data !== NULL && $this->data->isErrato())
			return false;
		$v = $this->getValore();
		if ($v === NULL) {
			$this->err = FORMERR_FORMAT;
			return false;
		}
		return true;
	}
	
	function getTipo() {
		return FORMELEM_TIME;
	}
}
