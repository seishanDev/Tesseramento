<?php
if (!defined('_BASE_DIR_')) exit();

//TODO chiavi non funzionano
class FormElem_File extends FormElem {
	private $dest = NULL;
	
	public function __construct($nome, $parent, $key=NULL, $obblig=false) {
		if ($key !== NULL) trigger_error('$key con File non funzionante'); //FIXME
		parent::__construct($nome, $parent, $key, $obblig, NULL);
		$this->persistente = false;
	}
	
	//nessuna persistenza per i file
	public function setPersistente($val) {}
	
	/**
	 * Restiuisce uno dei valori di $_FILES relativo a questo file
	 * @param string $key name, type, size, tmp_name, error
	 * @return mixed|NULL
	 */
	private function getFileInfo($key) {
		$raw = $this->getValoreRaw();
		if ($raw === NULL) return NULL;
		else return $raw[$key];
	}
	
	/**
	 * Restituisce il percorso del file
	 * @return string
	 */
	public function getPath() {
		if ($this->dest === NULL)
			return $this->getFileInfo('tmp_name');
		else
			return $this->dest;
	}
	
	/**
	 * Restituisce il nome originale del file
	 * @return string
	 */
	public function getNomeFile() {
		return $this->getFileInfo('name');
	}
	
	/**
	 * Restituisce la dimensione del file in byte
	 * @return int
	 */
	public function getDimensione() {
		return $this->getFileInfo('size');
	}
	
	/**
	 * Restituisce il percorso del file temporaneo
	 * @see FormElem::getValore()
	 */
	public function getValore() {
		return $this->getFileInfo('tmp_name');
	}
	
	public function haValore() {
		$err = $this->getFileInfo('error');
		return $err !== NULL && $err != UPLOAD_ERR_NO_FILE;
	}
	
	public function isValido() {
		if (!parent::isValido()) return false;
		$err = $this->getFileInfo('error');
		if ($err !== NULL && $err !== UPLOAD_ERR_OK
				&& $err !== UPLOAD_ERR_NO_FILE)
		{
			$this->err = $err;
			return false;
		}
		return true;
	}
	
	public function getTipo() {
		return FORMELEM_FILE;
	}
	
	/**
	 * Sposta il file caricato
	 * @param string $dest il percorso di destinazione
	 * @return boolean
	 */
	public function sposta($dest) {
		if ($this->dest !== NULL) return false; //già spostato
		if (move_uploaded_file($this->getFileInfo('tmp_name'), $dest)) {
			$this->dest = $dest;
			return true;
		} else 
			return false;
	}
}