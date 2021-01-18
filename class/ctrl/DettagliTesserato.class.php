<?php
if (!defined("_BASE_DIR_")) exit();
include_model('ModelFactory','Tesserato','Settore','Tipo');

class DettagliTesseratoCtrl {
	
	private $tes;
	private $qual = NULL;
	private $q_rinn = NULL;
	/**
	 * @var Crediti[][]
	 */
	private $crediti = NULL; 
	/**
	 * @var int[]
	 */
	private $crediti_tot = NULL; 
	
	function __construct($id_tes, $id_soc=NULL) {
		$this->tes = new Tesserato($id_tes);
		
		if(!$this->tes->esiste()) {go_home();}
		if($id_soc !== NULL && $id_soc != $this->tes->getIDSocieta()) {go_home();}
	}
	
	/**
	 * Restituisce il tesserato
	 * @return Tesserato
	 */
	function getTesserato() {
		return $this->tes;
	}
	
	/**
	 * Restituisce le qualifiche attive del tesserato
	 * @return Qualifica[] 
	 */
	public function getQualifiche() {
		if ($this->qual === NULL) {
			$anno_att = date('Y');
			$q = Qualifica::getListaAttive($this->tes, true);
			$this->qual = NULL;
			$this->q_rinn = array();
			$in_rinn = in_rinnovo();
			foreach ($q as $anno => $qa) {
				if ($this->qual === NULL)
					$this->qual = $qa;
				else 
					$this->qual += $qa;
				
				//se non siamo in rinnovo o le qualifiche sono dell'anno prox 
				if (!$in_rinn || $anno > $anno_att)
					$this->q_rinn = array_keys($qa);
			}
			if ($this->qual === NULL) $this->qual = array();
		}
		return $this->qual;
	}
	
	public function isQualificaRinnovata($idtipo) {
		if ($this->q_rinn === NULL) $this->getQualifiche();
		return in_array($idtipo, $this->q_rinn);
	}

	/**
	 * Restituisce il settore di cui fa parte una qualifica
	 * @param Qualifica $qualifica
	 * @return Settore
	 */
	public function getSettore($qualifica) {
		return Settore::fromId(Tipo::fromId($qualifica->getIdTipo())->getIDSettore());
	}
	
	/**
	 * Restituisce il grado di una qualifica
	 * @param Qualifica $qualifica
	 * @return Grado
	 */
	public function getGrado($qualifica) {
		return Grado::fromId($qualifica->getIdGrado());
	}
	
	/**
	 * Restituisce il tipo di una qualifica
	 * @param Qualifica $qualifica
	 * @return Tipo
	 */
	public function getTipo($qualifica) {
		return Tipo::fromId($qualifica->getIdTipo());
	}
	
	public function getGradi() {
		return $this->tes->getGradi();
	}
	
	public function getTipi() {
		return ModelFactory::lista('Tipo', $this->tes->getIDTipi());
	}
	
	/**
	 * @return string
	 */
	public function getDataNascita() {
		$dt = $this->tes->getDataNascita();
		if ($dt !== NULL)
			return $dt->format('d/m/Y');
		else
			return '';
	}
	
	/**
	 * Restituisce il luogo di nascita come "Luogo (PR)"
	 * @return string
	 */
	public function getLuogoNascita() {
		$nome = $this->tes->getLuogoNascita();
		$idp = $this->tes->getIDProvincia();
		if ($idp !== NULL) {
			include_model('Provincia');
			$p = Provincia::fromId($idp);
			if ($p !== NULL) {
				if ($nome === NULL || strlen($nome) == 0) 
					$nome = $p->getNome();
				return "$nome (".$p->getSigla().')';
			} else
				return $nome;
		} else 
			return $nome;
	}
	
	private function loadCrediti() {
		include_model('Crediti');
		$this->crediti = CreditiUtil::get()->getListaTess($this->tes->getId());
		foreach ($this->crediti as $idsett => $cl) {
			$this->crediti_tot[$idsett] = 0;
			foreach ($cl as $c) {
				/* @var $c Crediti */
				$this->crediti_tot[$idsett] += $c->getCrediti();
			}
		}
	}
	
	/**
	 * @param int $idsett
	 * @return int|NULL
	 */
	public function getTotCrediti($idsett) {
		if ($this->crediti_tot === NULL) 
			$this->loadCrediti();
		if (isset($this->crediti_tot[$idsett]))
			return $this->crediti_tot[$idsett];
		else
			return NULL;
	}
	
	/**
	 * @param int $idtipo
	 * @return Crediti[]
	 */
	public function getListaCrediti($idsett) {
		if ($this->crediti === NULL) 
			$this->loadCrediti();
		if (isset($this->crediti[$idsett]))
			return $this->crediti[$idsett];
		else
			return NULL;
	}
}