<?php
include_class('Data','Sesso','CodiceFiscale');
include_model('Tipo','Regione','Provincia','Comune','Societa');
include_form('Form');
include_form(FORMELEM_LIST, FORMELEM_DATE, FORMELEM_CHECK, FORMELEM_STATIC, FORMELEM_AUTOLIST, FORMELEM_GRADO);

define('FORMERR_CODFIS_COERENZA','formtesserato_codfis_coerenza');
define('FORMERR_TESSERATO_PRESENTE','formtesserato_tesserato_presente');

class FormTesserato extends Form {
	const NUM_TESS = 'num_tess';
	const COGN = 'cognome';
	const NOME = 'nome';
	const SESSO = 'sesso';
	const DATA_N = 'data_nascita';
	const REGIONE_N = 'regione_nascita';
	const PROV_N = 'prov_nascita';
	const LUOGO_N = 'luogo_nascita';
	const COD_FIS = 'cod_fis';
	const TEL = 'tel';
	const CELL = 'cell';
	const EMAIL = 'email';
	const CITT = 'citt';
	const INDIR = 'indirizzo';
	const CAP = 'cap';
	const CITTA_RES = 'citta_res';
	const REGIONE_RES = 'regione_res';
	const PROV_RES = 'prov_res';
			
	const TIPO = 'tipo';
	const GRADO = 'grado';
// 	const EXTRA = 'extra';
	
	public static function nomeExtra($idtipo, $key) {
		//TODO generalizzare costruzione nome, definita anche in DanView::stampa 
		return FormElem_Grado::getNomeExtra(self::GRADO, $key);
	}
	
	private $tesserato;
	private $completo;
	private $caricato = false;
	
	private $check = array();
	private $gradimod = array();
	/**
	 * Elementi relativi ai dati extra. 
	 * Formato: idtipo => chiave extra => FormElem
	 * @var FormElem_List[][]
	 */
	private $extra_el;
	
	/**
	 * @param string $nome
	 * @param Tesserato $tesserato il tesserato da modificare
	 * @param boolean $completo indica se è possibile modificare tutti i dati
	 * @param boolean[] chiave: idsettore; valore: true se è stato rinnovato 
	 */
	public function __construct($nome, $tesserato, $completo, $settori) {
		parent::__construct($nome);
		$this->tesserato = $tesserato;
		$this->completo = $completo;
		
		//mantendo l'id della federazione della società del tesserato
		if($tesserato->haId())
			$fed = Societa::fromId($tesserato->getIDSocieta())->getIdFederazione();
		else
			$fed = NULL;
		
// 		new FormElem_Static(self::NUM_TESS, $this, NULL, $tesserato->getNumTessera());

		if ($completo)
		{
			new FormElem(self::COGN, $this, NULL, true, $tesserato->getCognome());
			new FormElem(self::NOME, $this, NULL, true, $tesserato->getNome());
			$e = new FormElem_List(self::SESSO, $this, NULL, true, $tesserato->getSesso());
			$e->setValori(Sesso::getValoriLunghi());
			$e = new FormElem_Data(self::DATA_N, $this, NULL, true, $tesserato->getDataNascita());
			$e->setMax(DataUtil::get()->oggi());
		}
		else
		{
			new FormElem_Static(self::NOME, $this, NULL, $tesserato->getNome());
			new FormElem_Static(self::COGN, $this, NULL, $tesserato->getCognome());
			new FormElem_Static(self::SESSO, $this, NULL, Sesso::toStringLungo($tesserato->getSesso()));
			new FormElem_Static(self::DATA_N, $this, NULL, $tesserato->getDataNascita()->format('d/m/Y'));
		}
		
		$idprov = $tesserato->getIDProvincia();
		if ($idprov !== NULL)
			$idreg = Provincia::fromId($idprov)->getIDRegione();
		else
			$idreg = NULL;
		$reg = new FormElem_List(self::REGIONE_N, $this, NULL, false, $idreg);
		$reg->setValori(Regione::listaCompleta());
		$prov = new FormElem_AutoList(self::PROV_N, $this, NULL, false, $idprov);
		$prov->setSorgente($reg, 'province', array('Provincia','ajax'));
		
		new FormElem(self::LUOGO_N, $this, NULL, true, $tesserato->getLuogoNascita());
		new FormElem(self::COD_FIS, $this, NULL, true, $tesserato->getCodiceFiscale()); //TODO tipo codice fiscale
		new FormElem(self::TEL, $this, NULL, false, $tesserato->getTelefono());
		new FormElem(self::CELL, $this, NULL, false, $tesserato->getCellulare());
		new FormElem(self::EMAIL, $this, NULL, false, $tesserato->getEmail()); //TODO tipo email
		new FormElem(self::CITT, $this, NULL, false, $tesserato->getCittadinanza());
		
		new FormElem(self::INDIR, $this, NULL, true, $tesserato->getIndirizzo());
		new FormElem(self::CAP, $this, NULL, true, $tesserato->getCap());
		new FormElem(self::CITTA_RES, $this, NULL, true, $tesserato->getCittaRes());
		
		$idprov = $tesserato->getIDProvinciaRes();
		if ($idprov !== NULL) {
			$p = Provincia::fromId($idprov);
			if ($p === NULL) {
				$idprov = NULL;
				$idreg = NULL;
			} else
				$idreg = $p->getIDRegione();
		} else
			$idreg = NULL;
		$reg = new FormElem_List(self::REGIONE_RES, $this, NULL, true, $idreg);
		$reg->setValori(Regione::listaCompleta());
		$prov = new FormElem_AutoList(self::PROV_RES, $this, NULL, true, $idprov);
		$prov->setSorgente($reg, 'province', array('Provincia','ajax'));
		
		foreach($settori as $idset => $spag)
		{
			$tipi_sett = Tipo::getFromSettore($idset);
			foreach ($tipi_sett as $idtipo=>$tipo)
			{
				$q = $tesserato->getQualificaTipo($idtipo);
				if($q !== NULL)
				{
					if (!$spag) {
						$blocca = true;
					} else {
						$p = PagamentoUtil::get()->ultimoTipo($tesserato->getId(), $idtipo);
						$blocca = $p !== NULL && $p->isPagato() && (!in_rinnovo() || $p->isRinnovo());
					}
					if($blocca)
					{
						//ha già pagato
						$gval = NULL;
						if ($completo || $fed == 2) {
							//è completo, può modificare tutto
							//fa parte della federazione etsia, può modificare il grado
							$gval = Grado::listaTipo($idtipo);
						} else {
							//non è completo, vede che valori può modificare
							$gmod = $tipo->getGradiModificabili($q->getIdGrado());
							if ($gmod !== NULL)
								$gval = ModelFactory::lista('Grado', $gmod);
						}
						$grado = new FormElem_Grado(self::GRADO, $this, $idtipo, $q);
						if ($gval === NULL || count($gval) < 2) {
							//non c'è niente di modificabile
							$grado->setDisabilitato(true);
						} else {
							//può comunque modificare qualcosa
							$grado->setGradiValidi($gval);
							$this->gradimod[$idtipo] = $grado;
						}
					}
					else 
					{
						$ec = new FormElem_Check(self::TIPO, $this, $idtipo, true);
						$this->check[$idtipo] = $ec;
						new FormElem_Grado(self::GRADO, $this, $idtipo, $q);
					}
				}
				elseif ($spag)
				{
					//se il settore è stato pagato ma non ha ancora una qualifica
					$ec = new FormElem_Check(self::TIPO, $this, $idtipo, false);
					$this->check[$idtipo] = $ec;
					$tmpq = new Qualifica($tesserato, $idtipo);
					new FormElem_Grado(self::GRADO, $this, $idtipo, $q);
				}
			}
		}
		
	}
	
// 	/**
// 	 * Aggiunge gli elementi dei dati extra
// 	 * @param Qualifica $qual
// 	 * @param Tipo $tipo
// 	 * @param bool $static true per aggiungere gli elementi statici
// 	 */
// 	private function addDatiExtra($qual, $tipo, $edit) {
// 		if ($qual === NULL) return;
// 		$extra = $qual->getDatiExtra();
// 		if ($extra === NULL) return;
// 		$idtipo = $tipo->getId();
// 		foreach ($extra->getChiavi() as $key) {
// 			$nome = self::nomeExtra($idtipo, $key);
// 			if ($edit) {
// 				//campi extra modificabili
// 				$el = new FormElem_List($nome, $this, NULL, false, $extra->get($key));
// 				$el->setValori($extra->getValori($key));
// 				$this->extra_el[$idtipo][$key] = $el;
// 			} else {
// 				new FormElem_Static($nome, $this, NULL, $extra->toString($key));
// 			}
// 		}
// 	}
	
	function checkValidita($valido) {
		if (!$valido) return false;
		
		$err_check = false;
		foreach($this->check as $check)
		{
			/*@var $check FormElem_Check */
			if($check->getValore())
			{
				$grado = $this->getElem(self::GRADO, $check->getKey());
				if(!$grado->haValore())
				{
					$this->err[$grado->getNomeKey()] = FORMERR_OBBLIG;
					$err_check = true;
				}
			}
		}
		
		if($err_check) return false;
		
		$t = $this->getTesserato();
		if($t->getId() === NULL && TesseratoUtil::get()->cerca($t->getIDSocieta(), $t->getCognome(), $t->getNome(), $t->getDataNascita()) !== NULL)
		{
			$this->err[self::NOME] = FORMERR_TESSERATO_PRESENTE;
			return false;
		}
		
		if ($this->getTesserato()->getCodiceFiscale() === NULL) return true;
		include_class('CodiceFiscale');
		$r = CodiceFiscale::verifica($this->getTesserato());
		if ($r == CODFIS_OK) return true;
		if ($r == CODFIS_LUNGH || $r == CODFIS_CTRL) {
			//TODO rendere esplicito ctrl
			$this->err[self::COD_FIS] = FORMERR_FORMAT;
			return false;
		}
		switch ($r) {
			case CODFIS_COGNOME:
				$el = self::COGN;
				break;
			case CODFIS_NOME:
				$el = self::NOME;
				break;
			case CODFIS_ANNO:
			case CODFIS_MESE:
			case CODFIS_GIORNO:
				$el = self::DATA_N;
				break;
			case CODFIS_SESSO:
				$el = self::SESSO;
				break;
			default:
				//TODO luogo
				return false;
		}
		$this->err[$el] = FORMERR_CODFIS_COERENZA;
		return false;
	}
	
	public function getTesserato() {
		if (!$this->caricato) {
			$this->riempiTesserato($this->tesserato);
			$this->caricato = true;
		}
		return $this->tesserato;
	}
	
	/**
	 * 
	 * @param Tesserato $tes
	 */
	private function riempiTesserato($tes) {
		if($this->completo)
		{
			$tes->setCognome($this->getElem(self::COGN)->getValore());
			$tes->setNome($this->getElem(self::NOME)->getValore());
			$tes->setDataNascita($this->getElem(self::DATA_N)->getValore());
			$s = $this->getElem(self::SESSO)->getValoreRaw();
			if ($s == Sesso::M)
				$tes->setSesso(Sesso::M);
			elseif ($s == Sesso::F)
				$tes->setSesso(Sesso::F);
		}
		
		$cfel = $this->getElem(self::COD_FIS);
		if ($cfel->haValore()) {
			$cf = $cfel->getValore();
			$cf = CodiceFiscale::normalizza($cf);
			$tes->setCodiceFiscale($cf);
		} else {
			$tes->setCodiceFiscale(NULL);
		}

		$p = $this->getElem(self::PROV_N)->getValore();
		if ($p !== NULL) $p = $p->getId();
		$tes->setIDProvincia($p);

		$p = $this->getElem(self::PROV_RES)->getValore();
		if ($p !== NULL) $p = $p->getId();
		$tes->setIDProvinciaRes($p);
		
		$a = array(self::LUOGO_N=>'setLuogoNascita',
				self::TEL=>'setTelefono',
				self::CELL=>'setCellulare',
				self::EMAIL=>'setEmail',
				self::CITT=>'setCittadinanza',
				self::INDIR=>'setIndirizzo',
				self::CAP=>'setCap',
				self::CITTA_RES=>'setCittaRes'
		);
			
		foreach($a as $key=>$metodo){
			$val = $this->getElem($key)->getValore();
			if($val !== NULL)
			{
				$tes->$metodo($val);
			}
		}
			
// 		$el = $this->getElem(self::NUM_TESS);
// 		if($el !== NULL && !$el->isDisabilitato() && $el->getValore() !== NULL)
// 			$tes->setNumTessera($val);
		
		$gradi = array();
		foreach($this->check as $idtipo=>$check)
		{
			if($check->getValore())
			{
				/* @var $grado FormElem_Grado */
				$grado = $this->getElem(self::GRADO, $check->getKey());
				$idgrado = $grado->getGrado()->getValoreId();
				$this->tesserato->setQualifica($idtipo, $idgrado);
				$gradi[$idtipo] = $grado;
			}
			else 
			{
				$this->tesserato->rimuoviQualifica($idtipo);
			}
		}
		
		//aggiorna i gradi modificati
		foreach ($this->gradimod as $idtipo => $elg) {
			/* @var $elg FormElem_Grado */
			$this->tesserato->setQualifica($idtipo, $elg->getGrado()->getValoreId());
			$gradi[$idtipo] = $elg;
		}
		
		//salva i dati extra
		foreach ($gradi as $idtipo => $gel) {
			/* @var $gel FormElem_Grado */
			$q = $this->tesserato->getQualificaTipo($idtipo);
			if ($q !== NULL) {
				$extra = $q->getDatiExtra();
				if ($extra !== NULL) {
					foreach ($extra->getChiavi() as $key) {
						$el = $gel->getExtra($key);
						if ($el !== NULL)
							$extra->set($key, $el->getValoreId());
					}
				}
			}
		}
	}
} 
