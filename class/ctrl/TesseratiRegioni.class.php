<?php
if (!defined("_BASE_DIR_")) exit();
include_model('ModelFactory','Regione','Provincia','Comune','Societa','Tesserato','Settore');

class TesseratiRegioniCtrl {
	
	private $regioni;
	private $province;
	private $tot_reg = array();
	private $tot_prov = array();
	
	function __construct($anno) {
		
		$this->regioni = Regione::listaCompleta();
		
		foreach($this->regioni as $idr=>$reg)
		{
			$this->tot_reg[$idr] = 0;
			$this->province = Provincia::listaRegione($reg);
			
			foreach($this->province as $idp=>$prov)
			{
				$this->tot_prov[$idp] = 0;
				$comuni = Comune::listaProvincia($prov);
				
				foreach($comuni as $idc=>$com)
				{
					$societa = SocietaUtil::get()->listaComune($idc);
					
					foreach($societa as $ids=>$soc)
					{
						$tesserati = TesseratoUtil::get()->getAttivi($ids,$anno);
						
						$this->tot_reg[$idr] += count($tesserati);
						$this->tot_prov[$idp] += count($tesserati);
					}
				}
			}
		}
	}
	
	public function getRegioni()
	{
		return $this->regioni;
	}
	
	public function getProvince()
	{
		return $this->province;
	}
	
	public function getTotaliRegioni()
	{
		return $this->tot_reg;
	}
	
	public function getTotaliProvince()
	{
		return $this->tot_prov;
	}
}