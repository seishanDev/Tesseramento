<?phpif (!defined("_BASE_DIR_")) exit();include_model('ModelFactory','Societa','Tesserato','Tipo','Settore','Grado','Assicurazione','Tesserini');include_form('Form');include_form(FORMELEM_CHECK, FORMELEM_NUM);include_view('QualificaView');class ConfermaPolizzeCtrl {	const SOC_CH = 'societa_check';	const GENERA = 'genera';		private $form;	private $ar_soc;		public function __construct()	{		$this->form = new Form('conferma_polizze');		$this->ar_soc = Societa::listaCompleta();				}		public function stampa($id_soc)	{		$soc = Societa::fromId($id_soc);		$nome_soc = $soc->getNome();		$nome_file = $soc->getCodiceAcsi()."_".$soc->getNomeBreve().".csv";		$anno = DataUtil::get()->oggi()->getAnno();		if(in_rinnovo())		{			$ar = Tesserato::getRinnovatiProssimoAnno($soc->getId());			$anno++;		}		else 		{			$ar = Tesserato::getRinnovati($soc->getId());		}		$ar_tes = Tesserini::getSocieta($id_soc, $anno);				$str = "NOME;COGNOME;SOCIETA;DATA NASCITA;SETTORE;QUALIFICA;ANNO;NUMERO TESSERA;;\r\n";				foreach($ar as $id_t=>$tes)		{			/* @var $tes Tesserato */			$nome = strtoupper($this->cleanString($tes->getNome()));			$cognome = strtoupper($this->cleanString($tes->getCognome()));			$data_nasc = $tes->getDataNascita()->format('d/m/Y');			if(in_rinnovo())				$tess = AssicurazioneUtil::get()->getUltimaAssicurazione($tes->getId())->getTessera();			else 				$tess = $tes->getNumTessera();						if($tess == NULL)				continue;						foreach($tes->getQualifiche() as $qual)			{				/* @var $qual Qualifica */				$tipo = Tipo::fromId($qual->getIdTipo());								if(!$this->isGenerato($ar_tes, $id_t, $tipo->getId(), $anno))				{					$settore = Settore::fromId($tipo->getIDSettore());					$grado = Grado::fromId($qual->getIdGrado());										$n_settore = $settore->getNome();					$n_grado = utf8_decode(QualificaViewUtil::get()->getNome($qual));										$t = new Tesserini();										$t->setAnno($anno);					$t->setIDSocieta($id_soc);					$t->setIDTesserato($id_t);					$t->setIDTipo($tipo->getId());					$t->salva();										$str .= "$nome;$cognome;$nome_soc;$data_nasc;$n_settore;$n_grado;$anno;$tess;;\r\n";				}			}		}				$cons = $soc->getConsiglio();		$ruoli = Consiglio::getRuoli();				foreach ($ruoli as $ruolo)		{			$t_c = $cons->getMembro($ruolo);			if($t_c !== NULL)			{				if(!$this->isGenerato($ar_tes, $t_c->getId(), 0, $anno))				{					$nome = strtoupper($this->cleanString($t_c->getNome()));					$cognome = strtoupper($this->cleanString($t_c->getCognome()));					$data_nasc = $t_c->getDataNascita()->format('d/m/Y');					$n_ruolo = Consiglio::getRuoloStr($ruolo);										if($ruolo == Consiglio::DIRETTORETECNICO)						$tess = $t_c->getNumTessera();					else 						$tess = ";";										$t = new Tesserini();											$t->setAnno($anno);					$t->setIDSocieta($id_soc);					$t->setIDTesserato($t_c->getId());					$t->setIDTipo(0);					$t->salva();										$str .= "$nome;$cognome;$nome_soc;$data_nasc;;$n_ruolo;$anno;$tess;;\r\n";				}			}					}				return array($nome_file,$str);	}		private function creaZip($files) {		$zip = new ZipArchive();			$filename = tempnam('/tmp', 'acsi');			if ($zip->open($filename, ZIPARCHIVE::OVERWRITE)!==TRUE) {			Log::error("Impossibile aprire il file zip", $filename);			exit();		}			//riempie lo zip		foreach ($files as $nome => $cont) {			$zip->addFromString($nome,$cont);		}		$zip->close();			return $filename;	}		public function cleanString($string){		$string = str_replace("à", "a", $string);		$string = str_replace("á", "a", $string);		$string = str_replace("â", "a", $string);		$string = str_replace("ä", "a", $string);			$string = str_replace("è", "e", $string);		$string = str_replace("é", "e", $string);		$string = str_replace("ê", "e", $string);		$string = str_replace("ë", "e", $string);			$string = str_replace("ì", "i", $string);		$string = str_replace("í", "i", $string);		$string = str_replace("î", "i", $string);		$string = str_replace("ï", "i", $string);			$string = str_replace("ò", "o", $string);		$string = str_replace("ó", "o", $string);		$string = str_replace("ô", "o", $string);		$string = str_replace("ö", "o", $string);			$string = str_replace("ù", "u", $string);		$string = str_replace("ú", "u", $string);		$string = str_replace("û", "u", $string);		$string = str_replace("ü", "u", $string);			return $string;	}		public function isGenerato($ar_tes, $id_tes, $id_tipo, $anno)	{		foreach($ar_tes as $id_t=>$tes)		{			/* @var $tes Tesserini */			if($tes->getAnno() == $anno)				if($tes->getIDTesserato() == $id_tes)					if($tes->getIDTipo() == $id_tipo)						return true;		}				return false;	}		public function getForm()	{		return $this->form;	}		public function getSocieta()	{		return $this->ar_soc;	}}