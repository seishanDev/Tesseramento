<?php
include_model('Settore','Comune','Provincia','Regione');
include_form('Form');
include_form(FORMELEM_LIST, FORMELEM_AUTOLIST, FORMELEM_DATE, FORMELEM_CHECK, FORMELEM_STATIC, FORMELEM_NUM);

class FormSocieta extends Form {
	
	const REGIONE = 'regione';
	const PROV = 'provincia';
	const ID_COMUNE = 'comune';
	const COD_ACSI = 'acsi';
	const FILE_ACSI = 'fileacsi';
	const NOME = 'nome';
	const NOME_BREVE = 'nomebreve';
	const DATA_COST = 'data_cost';
	const P_IVA = 'p_iva';
	const SEDE_LEG = 'sede_legale';
	const CAP = 'cap';
	const TEL = 'tel';
	const FAX = 'fax';
	const EMAIL = 'email';
	const WEB = 'web';
	const DATA_INS = 'data_inserimento';
	
	private $soc;
	private $completo;
	
	
	/**
	 * 
	 * @param string $nome
	 * @param Societa $soc
	 * @param boolean $completo
	 * @param string $checkid
	 * @param boolean $post
	 */
	public function __construct($nome, $soc, $completo)
	{
		parent::__construct($nome);
		$this->soc = $soc;
		$this->completo = $completo;
		$this->addElems($completo);
	}
	
	private function addElems($completo)
	{
		if($completo)
		{
			include_form(FORMELEM_FILE);
			
			$el = new FormElem_Num(self::COD_ACSI, $this, NULL, false, $this->soc->getCodiceAcsi());
			$el->setNegValido(false);
			$el->setZeroValido(false);
			
			new FormElem_File(self::FILE_ACSI, $this);
			
// 			new FormElem(self::ID_COMUNE, $this, NULL, true, $this->soc->getIdComune());//TODO
			$idcomune = $this->soc->getIdComune();
			if ($idcomune === NULL) {
				$idreg = NULL;
				$idprov = NULL;
			} else {
				$tmp = Comune::fromId($idcomune);
				$idprov = $tmp->getIDProvincia();
				$tmp = Provincia::fromId($idprov);
				$idreg = $tmp->getIDRegione();
			}
			$reg = new FormElem_List(self::REGIONE, $this, NULL, true, $idreg);
			$reg->setValori(Regione::listaCompleta());
			$prov = new FormElem_AutoList(self::PROV, $this, NULL, true, $idprov);
			$prov->setSorgente($reg, 'province', array('Provincia','ajax'));
			$com = new FormElem_AutoList(self::ID_COMUNE, $this, NULL, true, $idcomune);
			$com->setSorgente($prov, 'comuni', array('Comune','ajax'));
			
			new FormElem(self::NOME, $this, NULL, true, $this->soc->getNome());
			new FormElem(self::NOME_BREVE, $this, NULL, true, $this->soc->getNomeBreve());
			new FormElem_Data(self::DATA_COST, $this, NULL, false, $this->soc->getDataCostituzione());
			new FormElem(self::P_IVA, $this, NULL, true, $this->soc->getPIva());
			new FormElem(self::SEDE_LEG, $this, NULL, true, $this->soc->getSedeLegale());
			new FormElem(self::CAP, $this, NULL, true, $this->soc->getCAP());
			
			//TODO possibilità di modificare i settori
		}
		else 
		{
			new FormElem_Static(self::COD_ACSI, $this, NULL, $this->soc->getCodiceAcsi());
			
			$idcomune = $this->soc->getIdComune();
			if ($idcomune === NULL) {
				$reg = '';
				$prov = '';
				$comune = '';
			} else {
				$tmp = Comune::fromId($idcomune);
				$comune = $tmp->getNome();
				$tmp = Provincia::fromId($tmp->getIDProvincia());
				$prov = $tmp->getNome();
				$tmp = Regione::fromId($tmp->getIDRegione());
				$reg = $tmp->getNome();
			}
			new FormElem_Static(self::REGIONE, $this, NULL, $reg);
			new FormElem_Static(self::PROV, $this, NULL, $prov);
			new FormElem_Static(self::ID_COMUNE, $this, NULL, $comune);
			
			new FormElem_Static(self::NOME, $this, NULL, $this->soc->getNome());
			new FormElem_Static(self::NOME_BREVE, $this, NULL, $this->soc->getNomeBreve());
			
			if($this->soc->getDataCostituzione() === NULL)
				$data = '';
			else 
				$data = $this->soc->getDataCostituzione()->format('d/m/Y');
			
			new FormElem_Static(self::DATA_COST, $this, NULL, $data);
			new FormElem_Static(self::P_IVA, $this, NULL, $this->soc->getPIva());
			new FormElem_Static(self::SEDE_LEG, $this, NULL, $this->soc->getSedeLegale());
			new FormElem_Static(self::CAP, $this, NULL, $this->soc->getCAP());
		}
		
		new FormElem(self::TEL, $this, NULL, true, $this->soc->getTel());
		new FormElem(self::FAX, $this, NULL, false, $this->soc->getFax());
		new FormElem(self::EMAIL, $this, NULL, true, $this->soc->getEmail());
		new FormElem(self::WEB, $this, NULL, false, $this->soc->getSito());
	}
	
	protected function checkValidita($valido) {
		if ($this->completo) {
			$err = false;
			$file = $this->getElem(self::FILE_ACSI);
			$cod = $this->getElem(self::COD_ACSI);
			$hafile = $file->haValore() || $this->soc->isFileAcsiEsistente();
			
			if ($cod->haValore()) {
				if (!$hafile) {
					//il file non c'è e non è stato caricato
					$file->setErrore(FORMERR_OBBLIG);
					$err = true;
				}
			} elseif ($hafile) {
				//il file è stato caricato ma non è stato inserito il codice
				$cod->setErrore(FORMERR_OBBLIG);
				$err = true;
			}
			if ($err) return false;
		}	
		return true;
	}
	
	public function getSocieta()
	{
		$soc = $this->soc;
		
		if($this->completo)
		{
			$el = $this->getElem(self::COD_ACSI);
			if ($el->haValore())
				$soc->setCodiceAcsi($el->getValore());
			else
				$soc->setCodiceAcsi(NULL);
			/* @var $el FormElem_File */
			$el = $this->getElem(self::FILE_ACSI);
			$el->sposta(_BASE_DIR_.$soc->getFileAcsi());
			
			$c = $this->getElem(self::ID_COMUNE)->getValore();
			if ($c === NULL)
				$soc->setIDComune(NULL);
			else 
				$soc->setIDComune($c->getId());
			
			$soc->setNome($this->getElem(self::NOME)->getValore());
			$soc->setNomeBreve($this->getElem(self::NOME_BREVE)->getValore());
			$soc->setDataCostituzione($this->getElem(self::DATA_COST)->getValore());
			$soc->setPIva($this->getElem(self::P_IVA)->getValore());
			$soc->setSedeLegale($this->getElem(self::SEDE_LEG)->getValore());
			$soc->setCAP($this->getElem(self::CAP)->getValore());
		}
		
		$soc->setTel($this->getElem(self::TEL)->getValore());
		$soc->setFax($this->getElem(self::FAX)->getValore());
		$soc->setEmail($this->getElem(self::EMAIL)->getValore());
		$soc->setSito($this->getElem(self::WEB)->getValore());
		
		return $soc;
	}
}