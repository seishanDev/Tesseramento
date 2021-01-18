<?php
if (!defined('_BASE_DIR_')) exit();

/**
 * Numero intero
 */
class FormElem_Num extends FormElem {
	private $zero = true;
	private $neg = true;
	private $val = 'no';
	
	function getValore() {
		if ($this->val === 'no') {
			$v = $this->getValoreRaw();
			if ($v === NULL) return NULL;
			$v = trim($v);
			if ($v == '') return NULL;
			$this->val = $this->calcolaValore($v);
		}
		return $this->val;
	}
	
	public function haValore() {
		$val = $this->getValore();
		if ($val === NULL)
			return false;
		else
			return true;
	}
	
	/**
	 * @param string $v
	 */
	protected function calcolaValore($v) {
		preg_replace('/^\s*(-?)\s*0*([1-9][0-9]*|0)\s*$/', '$1$2', $v);
		return intval($v);
	}

	public function isZeroValido() {
		return $this->zero;
	}
	
	public function setZeroValido($val) {
		$this->zero = $val;
	}

	public function isNegValido() {
		return $this->neg;
	}
	
	public function setNegValido($val) {
		$this->neg = $val;
	}
	
	public function accettaDecimali() {
		return false;
	}
	
	function isValido() {
		if (!parent::isValido()) return false;
		$v = $this->getValore();
		if ($v === NULL) return true;
		if ($v == 0 && !$this->zero)
			return false;
		if ($v < 0 && !$this->neg)
			return false;
		return true;
	}
	
	/**
	 * Restituisce l'espressione regolare che rappresenta il testo valido
	 * per questo campo
	 * @param boolean $noobblig [def:true] true per considerare l'eventuale non obbligatorietà del campo
	 */
	public function getRegex($noobblig=true) {
		$regex = '[0-9]';
		//gestione dello 0
		if ($this->zero)
			$regex .= '+';
		else
			$regex = "0*[1-9]$regex*";
		//gestione del negativo
		if ($this->neg)
			$regex = "(-\\s*)?$regex";

		if ($noobblig && !$this->isObbligatorio())
			$regex = "($regex|)";
		
		return "^\\s*$regex\\s*$";
	}
	
	function getTipo() {
		return FORMELEM_NUM;
	}
}