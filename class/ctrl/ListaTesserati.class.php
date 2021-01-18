<?php
if (!defined("_BASE_DIR_")) exit();
include_form('Form');
include_class('Sesso');
include_model('Tesserato','Tipo','Societa');
include_form(FORMELEM_CHECK, FORMELEM_STATIC);

class ListaTesseratiCtrl{
	
	private $tes_soc = array();
	private $soc;
	
	
	public function __construct($id_soc, $idtipo)
	{
		$this->soc = Societa::fromId($id_soc);
		if($this->soc === NULL) {go_home();}
		
		$this->form = new Form('lista_tess');
		
		$ltes = Tesserato::getInSocieta($id_soc);
		foreach($ltes as $idtes=>$tes)
		{
			/* @var $tes Tesserato */
			$q = $tes->getQualificaTipo($idtipo);
			
			if($q !== NULL)
				$this->tes_soc[$idtes] = $tes;
		}
		uasort($this->tes_soc, array("Tesserato","compare"));
		
	}
	
	public function getTesserati()
	{
		return $this->tes_soc;
	}
}