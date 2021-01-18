<?php
if (!defined("_BASE_DIR_")) exit();
include_model('Tesserato','Tipo');
include_form('Form');
include_form(FORMELEM_CHECK);

class ArbitriCtrl {
	
	private $ar_tes;
	
	public function __construct()
	{
		$ar_f = $this->stampa();
		$ar_file[$ar_f[0]] = $ar_f[1];
		
		$filename = $this->creaZip($ar_file);
				
		$filesize = filesize($filename);
		if ($filesize == 0) {
			Log::error('File zip tesserine vuoto',$filename);
			exit();
		}
					
		$filename_out = 'arbitri_'.date('Y-m-d_H-i-s');
		header("Content-Disposition: attachment; filename=$filename_out.zip");
		header("Content-Type: application/zip");
		header("Content-length: $filesize\n\n");
		header("Content-Transfer-Encoding: binary");
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
				
		readfile($filename);
	}
	
	public function stampa()
	{
		$ar_tes = Tesserato::getRinnovatiTipo(3);
		$ar_soc = Societa::listaCompleta();
		$nome_file = "Arbitri.csv";
	
		$str = "NOME;COGNOME;SOCIETA;DATA NASCITA;SETTORE;QUALIFICA;ANNO;NUMERO TESSERA;;\r\n";
	
		foreach($ar_tes as $id_t=>$tes)
		{
			/* @var $tes Tesserato */
			$nome = strtoupper($this->cleanString($tes->getNome()));
			$cognome = strtoupper($this->cleanString($tes->getCognome()));
			$data_nasc = $tes->getDataNascita()->format('d/m/Y');
			$tess = $tes->getNumTessera();
			$soc = $ar_soc[$tes->getIDSocieta()];
			$nome_soc = $soc->getNomeBreve();
			$anno = DataUtil::get()->oggi()->getAnno();
				
			foreach($tes->getQualifiche() as $qual)
			{
				if($qual->getIdTipo() != 3)
					continue;
				/* @var $qual Qualifica */
				$tipo = Tipo::fromId($qual->getIdTipo());
				$settore = Settore::fromId($tipo->getIDSettore());
				$grado = Grado::fromId($qual->getIdGrado());
	
				$n_settore = $settore->getNome();
				$n_grado = $grado->getNome();
	
				$str .= "$nome;$cognome;$nome_soc;$data_nasc;$n_settore;$n_grado;$anno;$tess;;\r\n";
			}
		}
	
		return array($nome_file,$str);
	}
	
	private function creaZip($files) {
		$zip = new ZipArchive();
	
		$filename = tempnam('/tmp', 'acsi');
	
		if ($zip->open($filename, ZIPARCHIVE::OVERWRITE)!==TRUE) {
			Log::error("Impossibile aprire il file zip", $filename);
			exit();
		}
	
		//riempie lo zip
		foreach ($files as $nome => $cont) {
			$zip->addFromString($nome,$cont);
		}
		$zip->close();
	
		return $filename;
	}
	
	public function cleanString($string){
		$string = str_replace("à", "a", $string);
		$string = str_replace("á", "a", $string);
		$string = str_replace("â", "a", $string);
		$string = str_replace("ä", "a", $string);
	
		$string = str_replace("è", "e", $string);
		$string = str_replace("é", "e", $string);
		$string = str_replace("ê", "e", $string);
		$string = str_replace("ë", "e", $string);
	
		$string = str_replace("ì", "i", $string);
		$string = str_replace("í", "i", $string);
		$string = str_replace("î", "i", $string);
		$string = str_replace("ï", "i", $string);
	
		$string = str_replace("ò", "o", $string);
		$string = str_replace("ó", "o", $string);
		$string = str_replace("ô", "o", $string);
		$string = str_replace("ö", "o", $string);
	
		$string = str_replace("ù", "u", $string);
		$string = str_replace("ú", "u", $string);
		$string = str_replace("û", "u", $string);
		$string = str_replace("ü", "u", $string);
	
		return $string;
	}
	
	public function getTesserati()
	{
		return $this->ar_tes;
	}
	
}