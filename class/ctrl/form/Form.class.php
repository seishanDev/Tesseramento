<?php
if (!defined("_BASE_DIR_")) exit();

define('FORMELEM_SUBMIT','submit');
define('FORMELEM_TEXT','text');
define('FORMELEM_HIDDEN','hidden');
define('FORMELEM_PASSWORD','password');
define('FORMELEM_DATE','data');
define('FORMELEM_TIME','orario');
define('FORMELEM_LIST','list');
define('FORMELEM_AUTOLIST','autolista');
//define('FORMELEM_TEXTAREA','textarea');
define('FORMELEM_NUM','numero');
define('FORMELEM_DEC','decimale');
define('FORMELEM_CHECK','checkbox');
define('FORMELEM_STATIC','static');
define('FORMELEM_FILE','file');
define('FORMELEM_GRADO','grado');

/**
 * Nessun errore 
 */
define('FORMERR_NO','def_noerr');
/**
 * Elemento obbligatorio non compilato
 */
define('FORMERR_OBBLIG','def_obblig');
/**
 * FormID: form già inviato
 */
define('FORMERR_RESEND','formid_resend');
/**
 * Formato non valido
 */
define('FORMERR_FORMAT','def_format');

class Form {
	/**
	 * Nome del form
	 * @var string
	 */
	private $nome;
	/**
	 * Metodo per l'invio dei dati
	 * @var boolean true = POST, false = GET
	 */
	private $post;
	/**
	 * Componenti del form
	 * @var FormElem[]
	 */
	protected $elems = array();
	
	protected $elems_anon = array();
	
	/**
	 * 
	 * @var FormElem[][] 
	 */
	private $multiel;
	
	private $submit = NULL;
	
	/**
	 * Elemento che indica se il form è stato inviato
	 * @var string
	 */
	private $sentinella = NULL;
	
	private $persistente = true;
	
	protected $err = NULL;
	
	/**
	 * Crea un nuovo form
	 * @param strng $nome nome del form
	 * @param boolean $id [def: true] true per impedire l'invio di duplicati
	 * @param boolean $post [def: true] true per utilizzare POST, false per utilizzare GET
	 */
	public function __construct($nome, $checkid=true, $post=true) {
		$this->nome = $nome;
		if ($checkid) {
			$formid = $nome.'_id';
			new FormElem_FormID($formid, $this);
			$this->setSentinella($formid);
		}
		$this->post = $post;
	}
	
	/**
	 * Indica se usa POST o GET
	 * @return boolean true = POST, false = GET
	 */
	public function usaPost() {
		return $this->post;
	}
	
	public function getNome() {
		return $this->nome;
	}
	
	/**
	 * Indica se gli elementi persistenti devono utilizzare
	 * il valore inviato come default
	 * @return boolean
	 */
	public function isPersistente() {
		return $this->persistente;
	}
	
	/**
	 * Imposta se il form deve avere valorie persistenti
	 * @param boolean $val
	 */
	public function setPersistente($val) {
		$this->persistente = $val;
	}
	
	/**
	 * Indica se verrà effettivamente utilizzata la persistenza
	 * @return boolean
	 */
	public function usaPersistenza() {
		return $this->persistente && $this->isInviato();
	}
	

	/**
	 * Restituisce tutti gli elementi con nome
	 * @return FormElem[]
	 */
	public function getElems() {
		return $this->elems;
	}

	/**
	 * Restituisce tutti gli elementi di tipo FORMELEM_HIDDEN
	 * @return FormElem[]
	 */
	public function getHiddenElems() {
		$ret = array();
			foreach ($this->elems as $el) {
			/* @var $el FormElem */
			if ($el->getTipo() == FORMELEM_HIDDEN) 
				$ret[] = $el;
		}
			foreach ($this->elems_anon as $el) {
			/* @var $el FormElem */
			if ($el->getTipo() == FORMELEM_HIDDEN) 
				$ret[] = $el;
		}
		return $ret;
	}
	
	public function getSubmit() {
		return $this->submit;
	}
	
	/**
	 * Restituisce un elemento del form
	 * @param string $nome il nome dell'elemento
	 * @param mixed $key [opz] la chiave dell'elemento 
	 * @return FormElem o NULL se l'elemento non esiste
	 */
	public function getElem($nome,$key=NULL) {
		$nome .= FormElem::keyToString($key);
		if (isset($this->elems[$nome]))
			return $this->elems[$nome];
		return NULL;
	}
	
	/**
	 * Aggiunge un elemento al form
	 * @param FormElem $elem
	 */
	public function addElem($elem) {
		$nome = $elem->getNomeKey();
		if ($nome === NULL)
			$this->elems_anon[] = $elem;
		else {
			$this->elems[$nome] = $elem;
		}
	}
	
	/**
	 * Restituisce le chiavi inviate per un certo elemento. 
	 * Per avere le chiavi a livelli inferiori richiamare la funzione 
	 * specificando il valore $key
	 * @param string $nome
	 * @param string $key [opz]
	 * @return array
	 */
	public function getSentKeys($nome, $key=NULL) {
		$var = $this->getValori();
		if ($key === NULL) {
			if (isset($var[$nome]) && is_array($var[$nome]))
				return array_keys($var[$nome]);
			else
				return array();
		} elseif (!is_array($key)) {
			if (isset($var[$nome][$key]) && is_array($var[$nome][$key]))
				return array_keys($var[$nome][$key]);
			else
				return array();
		} else {
			$val = $var[$nome];
			foreach ($key as $kv) {
				if (!isset($val[$kv]))
					return array();
				else
					$val = $val[$kv];
			}
			if (is_array($val))
				return array_keys($val);
			else
				return array();
		}
	}
	
	public function addSubmit($testo, $nome=NULL) {
		$e = new FormElem_Submit($testo, $this, $nome);
		if ($this->submit === NULL) $this->submit = $e;
		//TODO va bene il submit gestito così?
	}
	
	public function setSentinella($nomeElem) {
		$this->sentinella = $nomeElem;
	}
	
	/**
	 * Indica se i dati inviati sono validi
	 * @return boolean
	 */
	public function isValido() {
		$this->err = array();
		$valido = true;
		foreach ($this->elems as $nome=>$elem) {
			/* @var $elem FormElem */
			if (!$elem->isValido()) {
				$valido = false;
			}
		}
		foreach ($this->elems_anon as $elem) {
			/* @var $elem FormElem */
			//TODO gestione errori anonimi?
			if (!$elem->isValido())
				$valido = false;
		}
		$valido &= $this->checkValidita($valido);
		foreach ($this->elems as $nome=>$elem) {
			/* @var $elem FormElem */
			if ($elem->isErrato())
				$this->err[$nome] = $elem->getErrore();
		}
		return $valido;
	}
	
	/**
	 * Effettua ulteriori controlli di validità
	 * @param boolean $valido true se tutti gli elementi sono validi
	 * @return boolean
	 */
	protected function checkValidita($valido) {
		return true;
	}
	
	public function getErrori() {
		if ($this->err === NULL) 
			$this->isValido();
		return $this->err;
	}
	
	/**
	 * Indica se il form è stato inviato
	 * @return boolean
	 */
	public function isInviato() {
		$val = $this->getValori();
		if ($this->sentinella === NULL) {
			return count($val) > 0;
		} else {
			return isset($val[$this->sentinella]);
		}
	}
	
	public function isInviatoValido() {
		return $this->isInviato() && $this->isValido();
	}
	
	/**
	 * Indica se i dati inviati sono validi, ignorando gli errori di tipo FORMERR_RESEND
	 */
	public function isValidoResend() {
		foreach ($this->getErrori() as $err) {
			if ($err !== FORMERR_RESEND) 
				return false;
		}
		return true;
	}
	
	public function getValori($tipo=NULL) {
		if ($tipo == FORMELEM_FILE)
			return $_FILES;
		elseif ($this->post)
			return $_POST;
		else
			return $_GET;
	}
}

class FormElem {
	/**
	 * Il nome dell'elemento
	 * @var string
	 */
	private $nome;
	/**
	 * Il form che contiene questo elemento
	 * @var Form
	 */
	private $parent;
	/**
	 * Elemento da mostrare come default
	 * @var mixed
	 */
	protected $default;
	/**
	 * campo obbligatorio
	 * @var boolean
	 */
	protected $obblig;
	/**
	 * campo disabilitato
	 * @var boolean
	 */
	protected $disabled=false;
	/**
	 * utilizza il valore invato come default
	 * @var boolean
	 */
	protected $persistente = true;
	
	protected $key = NULL;
	
	protected $err = NULL;

	public static function keyToString($key) {
		if ($key === NULL)
			return '';
		
		if (is_array($key))
			$kv = implode('][', $key);
		else
			$kv = $key;
		return "[$kv]";
	}
	/**
	 * Crea un nuovo elemento
	 * @param string $nome
	 * @param Form $parent
	 * @param boolean $obblig [def: false] true per rendere il campo obbligatorio
	 * @param mixed $default [opz] il valore di default
	 */
	public function __construct($nome, $parent, $key=NULL, $obblig=false, $default=NULL) {
		$this->nome = $nome;
		$this->parent = $parent;
		$this->key = $key;
		$this->obblig = $obblig;
		$this->default = $default;
		$parent->addElem($this);
	}
	
	public function getKey() {
		return $this->key;
	}
	
	/**
	 * @return string
	 */
	public function getNome() {
		return $this->nome;
	}
	
	/**
	 * Restituisce il nome dell'elemento compresa la chiave
	 * @return string
	 */
	public function getNomeKey() {
		if ($this->nome === NULL)
			return NULL;
		else
			return $this->nome . self::keyToString($this->key);
	}
	
	/**
	 * Restituisce il form che contiene questo elemento
	 * @return Form
	 */
	public function getForm() {
		return $this->parent;
	}

	/**
	 * Indica se deve essere utilizzato il valore inviato come default
	 * @return boolean
	 */
	public function isPersistente() {
		return $this->persistente;
	}
	
	/**
	 * Imposta se l'elemento deve avere un valore persistente
	 * @param boolean $val
	 */
	public function setPersistente($val) {
		$this->persistente = $val;
	}
	
	/**
	 * Restituisce il valore senza formattazione
	 * @return string o NULL se non è stato inviato nessun valore
	 */
	public function getValoreRaw() {
		$var = $this->parent->getValori($this->getTipo());
		if (!isset($var[$this->nome])) 
			return NULL;
		if ($this->key === NULL)
			return $var[$this->nome];
		
		if (!is_array($this->key)) {
			if (isset($var[$this->nome][$this->key]))
				return $var[$this->nome][$this->key];
			else
				return NULL;
		} else {
			$val = $var[$this->nome];
			foreach ($this->key as $kv) {
				if (!isset($val[$kv]))
					return NULL;
				else
					$val = $val[$kv];
			}
			return $val;
		}
	}
	
	public function getValore() {
		$val = $this->getValoreRaw();
		if ($val === NULL) return NULL;
		return trim($val);
	}
	
	/**
	 * Indica se l'elemento ha un valore di default
	 * @return boolean
	 */
	public function haDefault() {
		return $this->default != NULL;
	}
	
	protected function usaPersistenza() {
		return $this->persistente && $this->parent->usaPersistenza();
	}
	
	/**
	 * Restituisce il valore di default o il valore persistente
	 * @param bool $no_pers [def:false] true per restituire sempre il valore di default
	 * @return mixed
	 */
	public function getDefault($no_pers = false) {
		if (!$no_pers && $this->usaPersistenza()) {
			return $this->getValore();
		} else {
			return $this->default;
		}
	}
	
	public function setDefault($val) {
		$this->default = $val;
	}
	
	/**
	 * Verifica che i dati inseriti siano validi
	 * @return boolean
	 */
	public function isValido() {
		$this->err = FORMERR_NO;
		if ($this->obblig && !$this->haValore()) {
			$this->err = FORMERR_OBBLIG;
			return false;
		}
		return true;
	}
	
	/**
	 * Indica se è stato inviato un valore 
	 * @return boolean
	 */
	public function haValore() {
		$val = $this->getValore();
		if ($val === NULL || $val === '')
			return false;
		else
			return true;
	}
	
	/**
	 * Restituisce l'errore dell'elemento o FORMERR_NO se non ci sono errori
	 * @return una delel costanti FORMERR_*
	 */
	public function getErrore() {
		if ($this->err === NULL) 
			$this->isValido();
		return $this->err;
	}
	
	/**
	 * Indica se questo elemento ha errori
	 * @return boolean
	 */
	public function isErrato() {
		if ($this->err === NULL) 
			$this->isValido();
		return $this->err != FORMERR_NO;
	}
	
	public function setErrore($val) {
		$this->err = $val;
		
	}
	
	/**
	 * Indica se la compilazione dell'elemento è obbligatoria
	 * @return boolean
	 */
	public function isObbligatorio() {
		return $this->obblig;
	}
	
	/**
	 * Indica se l'elemento è disabilitato
	 * @return boolean
	 */
	public function isDisabilitato() {
		return $this->disabled;
	}
	
	/**
	 * Indica se l'elemento è disabilitato
	 * @param boolean $val
	 */
	public function setDisabilitato($val) {
		$this->disabled = $val;
	}
	
	/**
	 * Restituisce il tipo dell'elemento
	 * @return string una delle costanti FORMELEM_*
	 */
	public function getTipo() {
		return FORMELEM_TEXT;
	}
}

class FormElem_FormID extends FormElem {
	const SESS_KEY = 'form_lastformid';
	
	private $valido = NULL;
	
	public function __construct($nome, $parent) {
		parent::__construct($nome, $parent, NULL, true, md5(time()));
		$this->persistente = false;
	}
	
	public function isValido() {
		if ($this->valido === NULL) {
			$this->valido = parent::isValido();
			if (!$this->valido) return false;
			$val = $this->getValoreRaw();
			//è valido se l'ultimo form inviato non è questo
			if (!isset($_SESSION[self::SESS_KEY]) || $_SESSION[self::SESS_KEY] != $val) {
				$_SESSION[self::SESS_KEY] = $val;
				$this->valido = true;
			} else {
				$this->err = FORMERR_RESEND;
				$this->valido = false;
			}
		}
		return $this->valido;
	}
	
	public function getTipo() {
		return FORMELEM_HIDDEN;
	}
}

class FormElem_Submit extends FormElem {
	public function __construct($testo, $parent, $nome=NULL, $key=NULL) {
		parent::__construct($nome, $parent, $key, false, $testo);
		$this->persistente = false;
	}
	
	public function isPremuto() {
		return $this->getValoreRaw() !== NULL;
	}
	
	public function getTipo() {
		return FORMELEM_SUBMIT;
	}
}
