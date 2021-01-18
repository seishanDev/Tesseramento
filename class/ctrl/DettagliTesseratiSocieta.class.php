<?php
if (!defined("_BASE_DIR_")) exit();
include_model('ModelFactory','Societa','Tesserato');

class DettagliTesseratiSocietaCtrl {
	
	private $soc;
	private $attivi;
	private $non_attivi;
	private $rinnovati;
	
	function __construct($id_soc) {
		$this->soc = Societa::fromId($id_soc);
		
// 		if($this->soc === NULL || !$this->soc->esiste()) {go_home();}
		
		$this->attivi = Tesserato::getRinnovati($id_soc);
		$this->non_attivi = Tesserato::getNonAttivi($id_soc);
		
		if(in_rinnovo())
			$this->rinnovati = Tesserato::getRinnovatiProssimoAnno($id_soc);
		else
			$this->rinnovati = NULL;
		
	}
	
	/**
	 * Restituisce la societa
	 * @return Societa
	 */
	function getSocieta() {
		return $this->soc;
	}
	
	function getAttivi() {
		return $this->attivi;
	}
	
	function getNonAttivi() {
		return $this->non_attivi;
	}
	
	function getRinnovati(){
		return $this->rinnovati;
	}
}