<?php
if (!defined('_BASE_DIR_')) exit();
include_model('Societa','Pagamento');
include_form('Form');

class RinnovoCtrl {
	const FASE_SOC = 1;
	const FASE_SETT = 2;
	const FASE_CONS = 3;
	const FASE_TESS_1 = 4;
	const FASE_TESS_2 = 5;
	const FASE_TESS_3 = 6;
	const FASE_TESS_4 = 7;
	const FASE_TESS_5 = 8;
	const FASE_TESS_6 = 9;
	const FASE_FINE = 10;
	
	private $soc;
	private $fase;
	
	public function __construct($ids, $fase=NULL) {
		$this->soc = Societa::fromId($ids);
		$this->fase = self::FASE_SOC;
		switch ($fase) {
			case self::FASE_SOC:
				break;
			case self::FASE_SETT:
				$this->fase = self::FASE_SETT;
				break;
			case self::FASE_CONS:
			case self::FASE_TESS_1:
			case self::FASE_TESS_2:
			case self::FASE_TESS_3:
			case self::FASE_TESS_4:
			case self::FASE_TESS_5:
			case self::FASE_TESS_6:
			case self::FASE_FINE:
				if ($this->faseAttiva($fase))
					$this->fase = $fase;
				else
					$this->fase = self::FASE_SETT;
				break;
			default:
				if ($this->faseAttiva(self::FASE_TESS_1))
					$this->fase = self::FASE_TESS_1;
				else
					$this->fase = self::FASE_SOC;
		}
	}
	
	public function getFase() {
		return $this->fase;
	}
	
	/**
	 * @return boolean
	 */
	private function haPagamentiSettori() {
		if(in_rinnovo())
			return PagamentoUtil::get()->haSettoriRinnovati($this->soc->getId());
		else 
			return PagamentoUtil::get()->haSettoriInPagamento($this->soc->getId());
	}
	
	/**
	 * Indica se è possibile saltare ad una fase specifica
	 * @param int $fase la fase a cui si vuole saltare
	 * @return boolean
	 */
	public function faseAttiva($fase) {
		if ($fase == $this->fase)
			return true;
		switch ($fase) {
			case self::FASE_SOC:
			case self::FASE_SETT:
				return true;
			case self::FASE_CONS:
			case self::FASE_TESS_1:
			case self::FASE_TESS_2:
			case self::FASE_TESS_3:
			case self::FASE_TESS_4:
			case self::FASE_TESS_5:
			case self::FASE_TESS_6:
			case self::FASE_FINE:
				return $this->haPagamentiSettori();
		}
		return false;
	}
	
	/**
	 * @return int o NULL se questa è la prima fase
	 */
	public function getFasePrec() {
		if ($this->fase == self::FASE_SOC)
			return NULL;
		else
			return $this->fase - 1;
	}

	/**
	 * @return int o NULL se questa è l'ultima fase
	 */
	public function getFaseSucc() {
		if ($this->fase == self::FASE_FINE)
			return NULL;
		else
			return $this->fase + 1;
	}
}