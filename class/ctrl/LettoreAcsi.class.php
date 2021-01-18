<?php
if (!defined('_BASE_DIR_')) exit();
include_controller('Acsi');
include_model('Assicurazione','Tesserato');
include_class('Data');
include_form('Form');
include_form(FORMELEM_FILE);

class LettoreAcsiCtrl extends AcsiCtrl {
	const F_FILE = 'file';
	
	const OK = 'ok';
	
	const NON_TROVATO = 0;
	const INVIATO = 1;
	const NON_ASSOC = 2;
	const ASSOC_ERRATO = 3;
	
	private $form;
	private $stato = NULL;
	
	public function __construct() {
		$rinn = in_rinnovo();
		parent::__construct($rinn, false, true);
		
		$f = new Form('conferma_acsi');
		$this->form = $f;
		$el = new FormElem_File(self::F_FILE, $f, NULL, true);
		
		if($f->isInviatoValido()) {
			$res = $this->leggiFile($el->getPath());
			foreach ($res[self::OK] as $a) {
				$a->salva();
			}
			$this->init($rinn, false, true);
			$this->stato = self::OK;
		}
	}
	
	/**
	 * @return Form
	 */
	public function getForm() {
		return $this->form;
	}
	
	public function fileLetto() {
		return $this->stato == self::OK;
	}
	
	/**
	 * @param string $file
	 * @return Assicurazione[]
	 */
	private function leggiFile($file) {
		//legge il file per righe
		$cont = explode("\n", file_get_contents($file));
		
		require_once _BASE_DIR_.'phpexcel/Classes/PHPExcel/IOFactory.php';
		require_once _BASE_DIR_.'phpexcel/Classes/PHPExcel.php';
		
		$excel = PHPExcel_IOFactory::load($file);
		$excel->setActiveSheetIndex(0);
		$exit = false;
		$riga = 2;
		
		$oggi = DataUtil::get()->oggi();
		$res = array(self::OK => array());
		
		while(!$exit)
		{
			$ntess = intval($excel->getActiveSheet()->getCell("A".$riga)->getValue());
			$cogn = $excel->getActiveSheet()->getCell("B".$riga)->getValue();
			$nome = $excel->getActiveSheet()->getCell("C".$riga)->getValue();
			$codfis = $excel->getActiveSheet()->getCell("D".$riga)->getValue();
			$datana = $excel->getActiveSheet()->getCell("H".$riga)->getValue();
// 			var_dump($ntess);
			
			$cont = array();
			$cont[0] = $oggi->format("d/m/Y");
			$cont[1] = $ntess;
			$cont[2] = $cogn;
			$cont[3] = $nome;
			$cont[4] = "useless";
			$cont[5] = $datana;
			
			$this->analizzaRiga($cont, $res);
			
			$riga += 1;
			$prox = $excel->getActiveSheet()->getCell("A".$riga)->getValue();
			if($prox == NULL)
				$exit = true;
		}

		$nr = count($cont);
		//inizia da riga 3
		for($i=0; $i<$nr; $i++) {
			$this->analizzaRiga($cont[$i], $res);
		}
		return $res;
	}
	
	private function analizzaRiga($riga, &$res) {
		//formato file:
		//         0                1       2     3        4               5
		//dd-mm-yyyy_inserimento;tessera;cognome;nome;luogo_nascita;dd-mm-yyyy_nascita;...
		$col = $riga;//explode(';', $riga); //MODIFICATO 11/10/2016
		/*
		if (count($col) < 5) {
			return false;
		} elseif ($col[0] == '' || $col[0] == 'DATA ISCRIZIONE') {
			return false;
		}
		*/

		$ntess = $col[1];
		
		//nessun numero tessera
		if ($ntess == '') return false;
	
		$ins = $this->leggiData($col[0], $riga);
		$oggi = DataUtil::get()->oggi();
		if ($ins === NULL) return false;
		if ($ins->getAnno() < $oggi->getAnno())
			$ins = $oggi;
		
		$idt = AssicurazioneUtil::get()->tesseratoInviato($ntess);
		$t = NULL;
		$stato = self::NON_TROVATO;
		
		$cogn = $col[2];
		$nome = $col[3];
		$nasc = $this->leggiData($col[5], $riga);
		if ($nasc === NULL) return false;
		
		//verifica che ci sia un tesserato associato 
		if ($idt === NULL) {
			//la tessera non è stata associata, cerca il tesserato
			$stato = self::NON_ASSOC;
			
			//verifica se è già stato registrato
			$idt = AssicurazioneUtil::get()->cercaAssicurato($ntess);
			if ($idt !== NULL) return true; //TODO verificare che sia proprio lui?
			
			//la tessera non è associata, cerca il tesserato scritto nella riga
			$t = TesseratoUtil::get()->cerca(NULL, $cogn, $nome, $nasc);
		} else {
			$t = new Tesserato($idt);
		
			//verifica che il tesserato associato sia lo stesso di quello letto
			$stesso = (strcasecmp($cogn, $t->getCognome()) == 0);
			if ($stesso)
				$stesso &= (strcasecmp($nome, $t->getNome()) == 0);
			if ($stesso)
				$stesso &= ($t->getDataNascita()->confronta($nasc) == 0);
			
			if (!$stesso) {
				//cercare il vero tesserato
				$stato = self::ASSOC_ERRATO;
				$t = TesseratoUtil::get()->cerca(NULL, $cogn, $nome, $nasc);
			} else {
				$stato = self::INVIATO;
			}
		}
		
		//nessun tesserato trovato
		if ($t === NULL) return false;
		
		$oldidt = $idt;
		$idt = $t->getId();
		if ($oldidt === NULL)
			$oldidt = $idt;
		$res[self::OK][] = Assicurazione::crea($idt, $ntess, $ins);
		switch ($stato) {
			case self::INVIATO:
				$res[$stato][$idt] = $ntess;
				break;
			case self::NON_ASSOC:
			case self::ASSOC_ERRATO:
				$res[$stato][$idt] = $oldidt;
				break;
			case self::NON_TROVATO:
				$res[$stato][$ntess] = array('cognome'=>$cogn, 'nome'=>$nome, 'nascita'=>$nasc);
				break;
		}
		return true;
	}
	
	private function leggiData($val, $riga) {
		//formato gg-mm-aaaa
		$v = explode('-',$val);
		if (count($v) != 3) {
			//formato non gg-mm-aaaa, prova gg/mm/aaaa
			$d = DataUtil::get()->parseDMY($val);
			if ($d !== NULL) 
				return $d;
			else {
				//formato neanche gg/mm/aaaa, errore
				$this->errFormato($riga);
				return NULL;
			}
		}
		return new Data($v[2], $v[1], $v[0]);
	} 
	
	private function errFormato($riga) {
		Log::error('Formato ACSI non valido',$riga);
	}
}
