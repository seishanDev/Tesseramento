<?php
if (!defined('_BASE_DIR_')) exit();

define('FORMERR_DATA_MIN','data_min');
define('FORMERR_DATA_MAX','data_max');

class FormElem_Data extends FormElem {
	
	private $min = NULL, $max = NULL;
	
	
	function getMin() {
		return $this->min;
	}
	
	function getMax() {
		return $this->max;
	}
	
	/**
	 * Imposta la data minima valida (inclusa)
	 * @param Data $val
	 */
	function setMin($val) {
		$this->min = $val;
	}
	
	/**
	 * Imposta la data massima valida (inclusa)
	 * @param Data $val
	 */
	function setMax($val) {
		$this->max = $val;
	}
	
	function getValore() {
		$d = DataUtil::get()->parseDMY($this->getValoreRaw());
		return $d;
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
		$v = $this->getValore();
		if ($v === NULL) {
			$this->err = FORMERR_FORMAT;
			return false;
		}
		if ($this->min !== NULL && $v->confronta($this->min) < 0) {
			$this->err = FORMERR_DATA_MIN;
			return false;
		}
		if ($this->max !== NULL && $v->confronta($this->max) > 0) {
			$this->err = FORMERR_DATA_MAX;
			return false;
		}
		return true;
	}
	
	function getTipo() {
		return FORMELEM_DATE;
	}
}
