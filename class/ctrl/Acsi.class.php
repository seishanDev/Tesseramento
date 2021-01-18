<?php
if (!defined('_BASE_DIR_')) exit();
include_model('Assicurazione','ModelFactory','Societa','Tesserato');

class AcsiCtrl {
	/**
	 * @var Tesserato[][] formato: idsocieta => idtesserato => Tesserato
	 */
	protected  $lista = array();
	/**
	 * @var Societa[] formato: idsocieta=> Societa
	 */
	private $soc;
	
	protected $inviati = NULL;
	
	/**
	 * @param bool $rinnovo true per considerare solo pagamenti e assicurazioni rinnovati, 
	 * false per considerare tutti i pagamenti e le assicurazioni attive
	 * @param bool $nuovi true per includere i non tesserati
	 * @param bool $attesa true per includere gli invii non confermati
	 */
	public function __construct($rinnovo, $nuovi, $attesa) {
		$this->init($rinnovo, $nuovi, $attesa);
	}
	
	protected function init($rinnovo, $nuovi, $attesa) {
		$this->inviati = NULL;
		$this->lista = array();
		$lidt = array();
		if ($nuovi)
			$lidt = AssicurazioneUtil::get()->nonAssicurati($rinnovo);
		else
			$lidt = array();
		
		if ($attesa) {
			$this->inviati = AssicurazioneUtil::get()->inviati();
			foreach ($this->inviati as $idt => $tess)
				$lidt[$idt] = $idt;
		}
		
		$lt = ModelFactory::lista('Tesserato', $lidt);
		foreach($lt as $id=>$t) {
			/* @var $t Tesserato */
			$this->lista[$t->getIDSocieta()][$id] = $t;
		}
		$this->soc = ModelFactory::lista('Societa', array_keys($this->lista));
	}
	
	/**
	 * Restituisce l'elenco delle società con tesserati da assicurare
	 * @return Societa[]
	 */
	public function getElencoSocieta() {
		return $this->soc;
	}
	
	/**
	 * @param int $idsoc
	 * @return Societa
	 */
	public function getSocieta($idsoc) {
		if (isset($this->soc[$idsoc]))
			return $this->soc[$idsoc];
		else
			return NULL;
	}
	
	/**
	 * Restituisce l'elenco dei tesserati da assicurare di una società
	 * @param int $idsoc
	 * @return Tesserato[]
	 */
	public function getTesserati($idsoc) {
		if (isset($this->lista[$idsoc]))
			return $this->lista[$idsoc];
		else
			return array();
	}
	
	/**
	 * Restituisce il numero tessera associato ad un tesserato non assicurato
	 * @param int $idsoc
	 * @param int $idtess
	 * @return string
	 */
	public function getNumTessera($idsoc, $idtess) {
		
		if ($this->inviati !== NULL && isset($this->inviati[$idtess]))
			return $this->inviati[$idtess]; //già inviato
		else 
			return NULL; //non lo so
	}
	
	/**
	 * Indica se un tesserato è già stato inviato ed è in attesa di conferma
	 * @param int $idtess ID tesserato
	 * @return boolean
	 */
	protected function isInviato($idtess) {
		if ($this->inviati === NULL) return false;
		return isset($this->inviati[$idtess]);
	}
}