<?php
if (!defined('_BASE_DIR_')) exit();
include_form(FORMELEM_NUM);

/**
 * Numero decimale
 */
class FormElem_Decimale extends FormElem_Num {

	protected function calcolaValore($v) {
		preg_replace('/^\s*(-?)\s*0*([1-9][0-9]*|0)([.,][0-9]+|)\s*$/', '$1$2$3', $v);
		return floatval(str_replace(',', '.', $v));
	}
	
	public function accettaDecimali() {
		return true;
	}
	
	/**
	 * Restituisce l'espressione regolare che rappresenta il testo valido
	 * per questo campo
	 * @param boolean $noobblig [def:true] true per considerare l'eventuale non obbligatorietà del campo
	 */
	public function getRegex($noobblig=true) {
		//gestione dello 0
		if ($this->isZeroValido()) {
			$regex = '[0-9]+(|[.,][0-9]+)';
		} else {
			$regex = '0*(0[.,][0-9]*[1-9]|[1-9][0-9]*(|[.,][0-9]+))';
		}
		//gestione del negativo
		if ($this->isNegValido())
			$regex = "(-\\s*)?$regex";

		if ($noobblig && !$this->isObbligatorio())
			$regex = "($regex|)";
		
		return "^\\s*$regex\\s*$";
	}
}