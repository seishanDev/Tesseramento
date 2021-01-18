<?php
if (!defined("_BASE_DIR_")) exit();
include_model('ModelFactory', 'Settore', 'Societa','Federazione');

class DettagliSocietaCtrl {
	
	private $soc;
	private $reg;
	private $com;
	private $federazione;
	private $sett_rinn = NULL;
	
	function __construct($id_soc) {
		$this->soc = Societa::fromId($id_soc);
		
		if($this->soc === NULL || !$this->soc->esiste()) {go_home();}
		
		$idc = $this->soc->getIdComune();
		if ($idc === NULL) {
			$this->reg = '';
			$this->com = '';
		} else {
			include_model('Regione','Provincia','Comune');
			$comune = Comune::fromId($idc);
			$prov = Provincia::fromId($comune->getIDProvincia());
			$this->reg = Regione::fromId($prov->getIDRegione())->getNome();
			$this->com = $comune->getNome().' ('.$prov->getSigla().')';
		}
		
		$this->federazione = Federazione::fromId($this->soc->getIdFederazione());
	}
	
	/**
	 * Restituisce la societa
	 * @return Societa
	 */
	function getSocieta() {
		return $this->soc;
	}
	
	function getRegione() {
		return $this->reg;
	}
	
	function getComune() {
		return $this->com;
	}
	
	function getFederazione(){
		return $this->federazione->getNome();
	}
	
	/**
	 * Restituisce il consiglio della societa
	 * @return Consiglio
	 */
	function getConsiglio() {
		return $this->soc->getConsiglio();
	}
	
	/**
	 * restituisce i settori della societa
	 * @return Settore[]
	 */
	function getSettori() {
		return ModelFactory::lista('Settore', $this->soc->getIDSettori());
	}
	
	/**
	 * Indica se un settore è stato rinnovato
	 * @param int $idsett
	 */
	function settoreRinnovato($idsett) {
		if ($this->sett_rinn === NULL) {
			if (in_rinnovo()) {
				include_model('Pagamento');
				$this->sett_rinn = PagamentoUtil::get()->settoriRinnovati($this->soc->getId());
			} else {
				//tutti rinnovati
				$this->sett_rinn = $this->soc->getIDSettori();
			}
		}
		return in_array($idsett, $this->sett_rinn);
	}
}