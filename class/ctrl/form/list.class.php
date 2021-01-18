<?php
if (!defined('_BASE_DIR_')) exit();

/**
 * Elemento non nella lista
 */
define('FORMERR_OUT_LIST','list_out');

class FormElem_List extends FormElem {
	private $valori; 
	private $tostr = NULL;
	
	public function setValori($val, $tostr = NULL) {
		$this->valori = $val;
		if ($tostr !== NULL && is_callable($tostr))
			$this->tostr = $tostr;
		else
			$this->tostr = NULL;
	}
	
	function getValoreRaw() {
		$v = parent::getValoreRaw();
		if ($v === '') return NULL;
		else return $v;
	}
	
	function getValore() {
		return $this->getElemLista($this->getValoreRaw());
	}
	
	/**
	 * Restituisce l'ID del valore selezionato 
	 * o NULL se non è stato selezionato nessun valore valido
	 * @return mixed
	 */
	function getValoreId() {
		$id = $this->getValoreRaw();
		if (isset($this->valori[$id]))
			return $id;
		else
			return NULL; 
	}
	
	function getElemLista($id) {
		if (isset($this->valori[$id]))
			return $this->valori[$id];
		else
			return NULL; 
	}
	
	/**
	 * Converte un elemento in stringa
	 * @param unknown $val
	 * @return string
	 */
	public function valToString($val) {
		if ($this->tostr === NULL) 
			return $val;
		else {
			return call_user_func($this->tostr, $val);
		}
	}
	
	function getLista() {
		return $this->valori;
	}
	
	function isValido() {
		$this->err = FORMERR_NO;
		if (!$this->obblig) return true;
		
		$id = $this->getValoreRaw();
		if ($id === NULL) {
			$this->err = FORMERR_OBBLIG;
			return false;
		}
		if (!isset($this->valori[$id])) {
			$this->err = FORMERR_OUT_LIST;
			return false;
		}
		return true;
	}
	
	public function getDefault($no_pers = false) {
		if (!$no_pers && $this->usaPersistenza()) {
			return $this->getValoreRaw();
		} else {
			return $this->default;
		}
	}	
	
	function getTipo() {
		return FORMELEM_LIST;
	}
}
