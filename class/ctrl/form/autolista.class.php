<?php
if (!defined('_BASE_DIR_')) exit();

class FormElem_AutoList extends FormElem {

	private $src;
	private $ajax;
	private $callback;
	
	/**
	 * Imposta l'elemento da cui dipendono i valori
	 * @param FormElem $elem l'elemento da cui dipendono i valori
	 * @param string $ajax il nome della pagina da cui leggere i valori dinamicamente.
	 * Deve accettare il parametro "id" contenente il valore della sorgente
	 * @param callable $callback la funzione da chiamare per avere i valori dell'elenco.<br>
	 * <code> function callback(idsrc, idval=NULL)</code><br>
	 * - se $idval == NULL restituisce tutti i valori dell'elenco<br>
	 * - se $idval != NULL restituisce il valore specifico o NULL se non esiste
	 */
	public function setSorgente($elem, $ajax, $callback) {
		$this->src = $elem;
		$this->ajax = $ajax;
		$this->callback = $callback;
	}
	
	/**
	 * @return FormElem
	 */
	public function getSorgente() {
		return $this->src;
	} 
	
	/**
	 * @return string
	 */
	public function getAjax() {
		return $this->ajax;
	}
	
	public function getValore() {
		$raw = $this->getValoreRaw();
		if ($raw === NULL) return NULL;
		return call_user_func($this->callback, $this->src->getValoreRaw(), $raw);
	}

	public function getDefault($no_pers = false) {
		if (!$no_pers && $this->usaPersistenza()) {
			return $this->getValoreRaw();
		} else {
			return $this->default;
		}
	}
	
	public function getLista($id=NULL) {
		if ($id === NULL)
			$id = $this->src->getDefault();
		return call_user_func($this->callback, $id); 
	}
	
	public function valToString($val) {
		return $val;
	}
	
	function getTipo() {
		return FORMELEM_AUTOLIST;
	}
}
 