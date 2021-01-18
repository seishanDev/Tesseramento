<?php
if (!defined('_BASE_DIR_')) exit();
include_controller('Acsi');
include_model('Assicurazione');

class FileIscrizioneAcsiCtrl extends AcsiCtrl {
	private $ntess = -1;
	
	/**
	 * Restituisce il numero di tessere disponibili
	 * @return int
	 */
	public function tessereDisponibili() {
		if ($this->ntess < 0) {
			$this->ntess = AssicurazioneUtil::get()->tessereDisponibili();
		}
		return $this->ntess;
	}
	
	public function tessereAnniPassati() {
		return AssicurazioneUtil::get()->tessereAnniPassati();
	}

	public function getNumTessera($idsoc, $idtess) {
		$t = parent::getNumTessera($idsoc, $idtess);
		if ($t !== NULL) return $t;
		
		if (!isset($this->lista[$idsoc][$idtess])) return '';
		return AssicurazioneUtil::get()->associaTessera($idtess);
	}
	
	/**
	 * Indica se un tesserato è già stato inviato ed è in attesa di conferma
	 * @param int $idtess ID tesserato
	 * @return boolean
	 */
	public function isInviato($idtess) {
		return parent::isInviato($idtess);
	}
}