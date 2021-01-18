<?php
if (!defined('_BASE_DIR_')) exit();
include_model('Pagamento','Tipo','Tesserato');
include_form('Form');
include_form(FORMELEM_CHECK, FORMELEM_DEC);

class RegistraPagamentiCtrl {
	/**
	 * Totale non inserito
	 */
	const ERR_OBBLIG = 'formato';
	/**
	 * Formato totale non valido
	 */
	const ERR_FORMATO = 'formato';
	/**
	 * Totale selezionato non corrisponde al totale inserito
	 */
	const ERR_SEL = 'selezione';
	/**
	 * Errore durante il salvataggio
	 */
	const ERR_SALVA = 'salva';
	
	/**
	 * @var Pagamento[] formato idsettore => Pagamento
	 */
	private $settori_np;
	/**
	 * @var Pagamento[][][] formato idsettore => idtesserato => idtipo => Pagamento
	 */
	private $p_tess;
	/**
	 * @var int[][] formato idsettore => idtipo => idtipo
	 */
	private $tipi;
	/**
	 * @var Tesserato[][] formato idsettore => idtesserato => Tesserato
	 */
	private $tess;
	private $tot;
	private $idsett;
	private $form;
	private $err = array();
	
	public function __construct($idsoc) {
		$this->settori_np = array();
		$this->p_tess = array();
		$this->tipi = array();
		$this->tess = array();
		$this->idsett = array();
		
		$pag = PagamentoUtil::get()->nonPagati($idsoc);
		$sett = array();
		$sett_p = array();
		$this->tot = 0;
		$tess = array();
		foreach ($pag as $p) {
			if ($p->getIdSettore() !== NULL) {
				$idsett = $p->getIdSettore();
				$this->settori_np[$idsett] = $p;
				$sett[$idsett] = $p;
				$this->tot += $p->getQuotaEuro();
				$this->idsett[$idsett] = $idsett;
			} else {
				$idtess = $p->getIdTesserato();
				$idtipo = $p->getIdTipo();
				$tipo = Tipo::fromId($p->getIdTipo());
				$idsett = $tipo->getIDSettore();
				
				$this->p_tess[$idsett][$idtess][$idtipo] = $p;
				$this->tipi[$idsett][$idtipo] = $idtipo;
				if(!isset($tess[$idtess]))
					$tess[$idtess] = new Tesserato($idtess); 
				$this->tess[$idsett][$idtess] = $tess[$idtess];
				$this->tot += $p->getQuotaEuro();
				$this->idsett[$idsett] = $idsett;
			}
		}
		foreach (PagamentoUtil::get()->getSettoriPagati($idsoc) as $p) {
			$idsett = $p->getIdSettore();
			if (isset($this->p_tess[$idsett]) && !isset($sett[$idsett])) {
				$sett[$idsett] = $p;
				$sett_p[$idsett] = $p;
			}
		}
		
		$this->form = new FormPagamento($sett, $this->p_tess);
		
		if ($this->form->isInviato()) {
			if ($this->form->isValido()) {
				if ($this->formInviato($sett_p)) {
					ricarica();
				}
			} else {
				$err = $this->form->getElem(FormPagamento::TOT)->getErrore();
				switch ($err) {
					case FORMERR_OBBLIG:
						$this->err[] = self::ERR_OBBLIG;
						break;
					case FORMERR_FORMAT:
						$this->err[] = self::ERR_FORMATO;
				}
			}
		}
	}
	
	/**
	 * @param Pagamento[] $sett_p settori già pagati
	 * @return true se sono stati salvati dei pagamenti
	 */
	private function formInviato($sett) {
		$tot_sel = 0;
		$da_pagare = array();
		//aggiunge ai settori già pagati i settori selezionati
		foreach ($this->form->getSentKeys(FormPagamento::SETTORE) as $idsett) {
			if (isset($this->settori_np[$idsett]) && !isset($sett[$idsett])) {
				$p = $this->settori_np[$idsett];
				$sett[$idsett] = $p;
				$tot_sel += $p->getQuotaEuro();
				$da_pagare[] = $p;
			}
		}
		
		//aggiunge i tesserati dei settori selezionati
		foreach ($sett as $idsett => $psett) {
			$tess_sel = $this->form->getSentKeys(FormPagamento::TESS, $idsett);
			foreach ($tess_sel as $idtess) {
				if (isset($this->p_tess[$idsett][$idtess])) {
					//aggiunge tutti i pagamenti dei tipi del tesserato
					foreach ($this->p_tess[$idsett][$idtess] as $p) {
						$tot_sel += $p->getQuotaEuro();
						$da_pagare[] = $p;
					}
				}
			}				
		}
		
		//controlla se il totale calcolato corrisponde
		$err = false;
		if ($this->form->getElem(FormPagamento::TOT)->getValore() == $tot_sel) {
			foreach ($da_pagare as $p) {
				$p->setPagato();
				if (!$p->salva() && !$err) {
					$this->err[] = self::ERR_SALVA;
					$err = true;
				}
			}
			return true;
		} else {
			$this->err[] = self::ERR_SEL;
		}
		
		return false;
	}
	
	/**
	 * @return FormPagamento
	 */
	public function getForm() {
		return $this->form;
	}
	
	public function getErrori() {
		return $this->err;
	}
	
	/**
	 * Restituisce il totale da pagare
	 * @return float
	 */
	public function getPrezzoTotale() {
		return $this->tot;
	}
	
	/**
	 * Restituisce gli ID dei settori per cui esiste un pagamento non pagato
	 * @return int[]
	 */
	public function getIDSettori() {
		return $this->idsett;
	}
	
	public function getIDTipi($idsett) {
		if (isset($this->tipi[$idsett]))
			return $this->tipi[$idsett];
		else
			return array();
	}
	
	public function getTesserati($idsett) {
		if (isset($this->tess[$idsett]))
			return $this->tess[$idsett];
		else
			return array();
	}

	/**
	 * Restituisce il prezzo da pagare per un settore
	 * @param int $idsett
	 * @return float
	 */
	public function getPrezzoSettore($idsett) {
		if (!isset($this->settori_np[$idsett]))
			return 0;
		$p = $this->settori_np[$idsett];
		return $p->getQuotaEuro();
	}
	
	/**
	 * Restituisce il prezzo da pagare per un tipo di un tesserato
	 * @param int $idsett
	 * @param int $idtess
	 * @param int $idtipo
	 * @return float o NULL se non ci sono pagamenti
	 */
	public function getPrezzoTipo($idsett, $idtess, $idtipo) {
		if (!isset($this->p_tess[$idsett])) return NULL;
		if (!isset($this->p_tess[$idsett][$idtess])) return NULL;
		if (!isset($this->p_tess[$idsett][$idtess][$idtipo])) return NULL;
		$p = $this->p_tess[$idsett][$idtess][$idtipo];
		return $p->getQuotaEuro();
	}
	
	/**
	 * Restituisce il prezzo da pagare per un tesserato in un settore
	 * @param int $idsett
	 * @param int $idtess
	 * @return float o NULL se non ci sono pagamenti
	 */
	public function getTotaleTesserato($idsett, $idtess) {
		if (!isset($this->p_tess[$idsett])) return NULL;
		if (!isset($this->p_tess[$idsett][$idtess])) return NULL;
		$tot = 0;
		foreach ($this->p_tess[$idsett][$idtess] as $p) {
			/* @var $p Pagamento */
			$tot += $p->getQuotaEuro();
		}
		return $tot;
	}
}

class FormPagamento extends Form {
	const SETTORE = 'settore';
	const TESS = 'tess';
	const TOT = 'totale';
	
	/**
	 * @param Pagamento[] $sett pagamenti per i settori
	 * formato idsettore => Pagamento
	 * @param Pagamento[][][] $tipi pagamenti per i tipi dei tesserati
	 * formato idsettore => idtesserato => idtipo => Pagamento
	 * @param string $nome
	 */
	public function __construct($sett, $tipi, $nome='pagamenti') {
		parent::__construct($nome);
		new FormElem_Decimale(self::TOT, $this, NULL, true);
		foreach ($sett as $idsett => $p) {
			$pagato = $p->isPagato();
			$el = new FormElem_Check(self::SETTORE, $this, $idsett, $pagato);
			$el->setDisabilitato($pagato);
			$el->setPersistente(!$pagato); //se è pagato non è persistente
		}
		foreach ($tipi as $idsett => $ltess) {
			foreach ($ltess as $idtess => $ltipi) {
				new FormElem_Check(self::TESS, $this, array($idsett, $idtess));
			}
		}
	}
}