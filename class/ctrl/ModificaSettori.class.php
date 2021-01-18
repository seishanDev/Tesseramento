<?php
if (!defined('_BASE_DIR_')) exit();
include_model('Societa','Tipo','Pagamento');
include_form('Form');
include_form(FORMELEM_CHECK);

class ModificaSettoriCtrl {
	const F_SETT = 'settore';
	
	private $soc;
	private $form;
	
	/**
	 * @param integer $idsocieta
	 * @param bool $rinnovo [def:false] true per considerare solo i settori rinnovati 
	 * @param callable $callback funzione da chiamare se la modifica va a buon fine
	 */
	function __construct($idsocieta, $rinnovo=false, $callback=NULL) {
		$this->soc = new Societa($idsocieta);
		$f = new Form('rinnova_sett');
		$this->form = $f;
		$su = $this->soc->getIDSettoriUltimi();
		foreach (Settore::elenco() as $ids => $sett) {
			$e = new FormElem_Check(self::F_SETT, $f, $ids, in_array($ids, $su));
			$p = PagamentoUtil::get()->ultimoSettore($this->soc->getId(), $ids);
			if ($p !== NULL && $p->isPagato()) {
				//se quello pagato non è del rinnovo, non disabilitare
				if ($rinnovo && !$p->isRinnovo())
					$dis = false;
				else
					$dis = true;
			} else {
				$dis = false;
			}
			$e->setDisabilitato($dis);
			$e->setPersistente(!$dis);
		}
		
		if($f->isInviatoValido())
		{
			$add = array();
			$del = array();
			foreach (array_keys(Settore::elenco()) as $idsett) {
				$e = $f->getElem(self::F_SETT, $idsett);
				if (!$e->isDisabilitato()) {
					if ($e->getValore()) {
						$this->soc->aggiungiSettore($idsett);
					} else {
						$this->soc->rimuoviSettore($idsett);
					}
				}
			}
			
			$res = $this->soc->salva();
			if ($res) {
				//TODO anno da selezionare in base alla funzione
				$anno = intval(date('Y'));
				if (/*$rinnovo && */in_rinnovo()) $anno++;
				$res = PagamentoUtil::get()->aggiornaSettori($this->soc, $anno);
			}
			if ($res && $callback !== NULL && is_callable($callback))
				call_user_func($callback);
		}
	}
	
	public function getForm() {
		return $this->form;
	}

	public function getFederazione() {
		return $this->soc->getIdFederazione();
	}

}