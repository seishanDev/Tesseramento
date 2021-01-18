<?php
if (!defined("_BASE_DIR_")) exit();
include_model('Tesserato','Crediti','Settore');
include_form('Form');
include_form(FORMELEM_DATE,FORMELEM_NUM);

class ModificaCreditiCtrl {
	
	const F_DATA = 'data';
	const F_DESC = 'descrizione';
	const F_CREDITI = 'crediti';
	const SUBMIT_ADD = 'aggiungi';
	const SUBMIT_DEL = 'elimina';
	
	/**
	 * @var Form
	 */
	private $form;
	private $tes;
	/**
	 * @var Crediti[]
	 */
	private $crediti;
	private $err = array();
	private $stato = NULL;
	
	/**
	 * 
	 * @param int $idtess
	 * @param int $idsett
	 * @param callable $callback [opz] funzione da chiamare all'aggiunta o all'eliminazione.
	 * Il primo argomento è l'oggetto Crediti modificato, il secondo è true se è stato aggiunto 
	 * e false se è stato eliminato
	 */
	public function __construct($idtess, $idsett, $callback=NULL) {
		$this->tes = new Tesserato($idtess);
		
		if(!$this->tes->esiste() || Settore::fromId($idsett) === NULL) {
			go_home();
		}
		//TODO verificare che il tesserato abbia una qualifica di quel tipo?
		
		$this->crediti = CreditiUtil::get()->getListaTess($idtess, $idsett);
		
		$f = new Form('crediti');
		$this->form = $f;
		
		$elData = new FormElem_Data(self::F_DATA, $f, NULL, true);
		$elData->setMax(DataUtil::get()->oggi());
		$elDesc = new FormElem(self::F_DESC, $f, NULL, true);
		$elNum = new FormElem_Num(self::F_CREDITI, $f, NULL, true);
		
		new FormElem_Submit("Aggiungi", $f, self::SUBMIT_ADD);
		
		foreach ($this->crediti as $c) {
			new FormElem_Submit("Elimina", $f, self::SUBMIT_DEL, $c->getId());
		}
		
		$del = $f->getSentKeys(self::SUBMIT_DEL);
		if(count($del) == 1)
		{
			//elimina crediti 
			reset($del);
			$idc = current($del);
			foreach ($this->crediti as $k => $c) {
				if ($c->getId() == $idc) {
					$c->elimina();
					$this->stato = -1;
					if (is_callable($callback))
						call_user_func($callback, $c, false);
					unset($this->crediti[$k]);
					break;
				}
			}
		}
		elseif($f->isInviato()) 
		{
			if($f->isValido())
			{
				if($f->getElem(self::SUBMIT_ADD)->isPremuto())
				{
					//inserisce nuovi crediti
					$desc = $elDesc->getValore();
					$data = $elData->getValore();
					$crediti = $elNum->getValore();
					$c = Crediti::crea($idtess, $idsett, $desc, $data, $crediti);
					$salvato = $c->salva();
					if($salvato) $this->stato = $c->getId(); 
					if ($salvato && is_callable($callback))
						call_user_func($callback, $c, true);
					new FormElem_Submit("Elimina", $f, self::SUBMIT_DEL, $c->getId());
					$f->setPersistente(false);
					$this->crediti = CreditiUtil::get()->getListaTess($idtess, $idsett);
				}
			}
			else //inviato con errore
			{
				foreach ($this->form->getErrori() as $nome => $err) {
					switch ($err) {
						case FORMERR_OBBLIG:
							$msg = 'Campo obbligatorio';
							break;
						case FORMERR_FORMAT:
							$msg = 'Formato non valido';
							break;
						case FORMERR_DATA_MAX:
							$msg = 'La data dev\'essere nel passato';
							break;
						default:
							$msg = "Errore $err";
							break;
					}
					$this->err[$nome] = $msg;
				}
			}
		}
	}
	
	/**
	 * @return Tesserato
	 */
	public function getTesserato() {
		return $this->tes;
	}
	
	public function getForm() {
		return $this->form;
	}
	
	public function getCrediti() {
		return $this->crediti;
	}
	
	public function getErrori() {
		return $this->err;
	}
	
	public function getErrore($nome) {
		if (isset($this->err[$nome]))
			return $this->err[$nome];
		else
			return '';
	}
	
	/**
	 * Indica se sono stati eliminati crediti
	 * @return boolean
	 */
	public function haEliminato() {
		return ($this->stato == -1);
	}
	
	/**
	 * Indica se sono stati aggiunti crediti
	 * @return boolean
	 */
	public function haSalvato() {
		return ($this->stato !== NULL && $this->stato >= 0);
	}
	
	/**
	 * Indica se questi crediti sono stati appena aggiunti
	 * @param int $idc
	 * @return boolean
	 */
	public function isCreditoSalvato($idc) {
		return ($this->stato !== NULL && $this->stato == $idc);
	}
}