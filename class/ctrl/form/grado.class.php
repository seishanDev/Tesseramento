<?php
if (!defined('_BASE_DIR_')) exit();
include_form(FORMELEM_LIST);
include_model('Tipo','Grado');

class FormElem_Grado extends FormElem {
	protected $idtess;
	protected $idtipo;
	/**
	 * Lista dei gradi
	 * @var FormElem_List
	 */
	protected $grado;
	/**
	 * Liste dei dati extra
	 * @var FormElem_List[] formato nome extra => FormElem
	 */
	protected $extra = NULL;
	
	public static function getNomeGrado($nome) {
		return "{$nome}_grado";
	}
	
	public static function getNomeExtra($nome, $key) {
		return "{$nome}_tipoextra_$key";
	}
	
	/**
	 * @param string $nome
	 * @param Form $parent
	 * @param int $idtipo
	 * @param Qualifica $qualifica [opz]
	 * @param int $idtess [opz]
	 */
	public function __construct($nome, $parent, $idtipo, $qualifica=NULL, $idtess=NULL) {
		if ($idtess === NULL)
			$key = $idtipo;
		else
			$key = array($idtess, $idtipo);
		parent::__construct($nome, $parent, $key, false, $qualifica);
		$this->idtess = $idtess;
		$this->idtipo = $idtipo;
		
		//crea lista grado
		if ($qualifica === NULL)
			$def = NULL;
		else
			$def = $qualifica->getIdGrado();
		$this->grado = new FormElem_List(self::getNomeGrado($nome), $parent, $key, false, $def);
		$this->grado->setValori(Grado::listaTipo($idtipo));
		
		//crea liste extra
		//TODO trovare un modo migliore per sapere le chiavi extra
		if ($qualifica === NULL) {
			if ($idtess === NULL)
				$exq = new Qualifica(new Tesserato(-1), $idtipo); 
			else
				$exq = new Qualifica(new Tesserato($idtess), $idtipo);
		} else {
			$exq = $qualifica;
		}
		$extra = $exq->getDatiExtra();
		if ($extra !== NULL) {
			foreach ($extra->getChiavi() as $exkey) {
				$nome = self::getNomeExtra($nome, $exkey);
				//campi extra modificabili
				$el = new FormElem_List($nome, $parent, $key, false, $extra->get($exkey));
				$el->setValori($extra->getValori($exkey));
				$this->extra[$exkey] = $el;
			}
		}
	}
	
	/**
	 * @return FormElem_List
	 */
	public function getGrado() {
		return $this->grado;
	}
	
	/**
	 * @param string $key
	 * @return FormElem_List |NULL
	 */
	public function getExtra($key) {
		if ($this->extra !== NULL && isset($this->extra[$key]))
			return $this->extra[$key];
		else
			return NULL;
	}
	
	/**
	 * @param Grado[] $val
	 */
	public function setGradiValidi($val) {
		$this->grado->setValori($val);
	}
	
	public function getValoreRaw() {
		$res = array('grado' => $this->grado->getValoreRaw());
		if ($this->extra !== NULL) {
			foreach ($this->extra as $key => $exel)
			$res['extra'][$key] = $exel->getValoreRaw();
		}
		return $res;
	}

	public function getValore() {
		$res = array('grado' => $this->grado->getValore());
		if ($this->extra !== NULL) {
			foreach ($this->extra as $key => $exel)
			$res['extra'][$key] = $exel->getValore();
		}
		return $res;
	}
	
	public function haValore() {
		return $this->grado->haValore();
	}
	
	public function getDefault($no_pers = false) {
		if (!$no_pers && $this->usaPersistenza()) {
			return $this->grado->getValore();
		} else {
			return $this->default;
		}
	}
	
	public function setDefault($val) {
		parent::setDefault($val);
		//def grado
		if ($val === NULL)
			$def = NULL;
		else
			$def = $qualifica->getIdGrado();
		$this->grado->setDefault($def);
		//def extra
		if ($this->extra !== NULL) {
			if ($val === NULL)
				$exq = new Qualifica(new Tesserato($this->idtess), $this->idtipo);
			else
				$exq = $val;
			$extra = $exq->getDatiExtra();
			if ($extra !== NULL) {
				foreach ($this->extra as $key => $exel) {
					$exel->setDefault($extra->get($key));
				}
			}
		}
	}
	
	public function isValido() {
		$this->err = FORMERR_NO;
		$valido = true;
		
		if (!$this->grado->isValido()) {
			$this->err = $this->grado->getErrore();
			$valido = false;
		}
		
		if ($this->extra !== NULL) {
			foreach ($this->extra as $exel) {
				$v = $exel->isValido();
				//se è il primo errore trovato
				if ($valido && !$v) {
					$this->err = $exel->getErrore();
					$valido = false;
				}
			}
		}
		
		return $valido;
	}
	
	private function setFunc($func, $val) {
		$this->grado->$func($val);
		if ($this->extra !== NULL) {
			foreach ($this->extra as $exel) {
				$exel->$func($val);
			}
		}
	}
	
	public function setDisabilitato($val) {
		parent::setDisabilitato($val);
		$this->setFunc('setDisabilitato', $val);
	}
	
	public function setPersistente($val) {
		parent::setPersistente($val);
		$this->setFunc('setPersistente', $val);
	}
	
	public function getTipo() {
		return FORMELEM_GRADO;
	}
}