<?php
if (!defined("_BASE_DIR_")) exit();
include_model('RichiestaAff');

class ListaRichiesteAffCtrl {
	
	
	private $lis_rich;
	
	public function __construct()
	{
		$this->lis_rich = RichiestaAff::getInDB();
	}
	
	public function getListaRich()
	{
		return $this->lis_rich;
	}
}