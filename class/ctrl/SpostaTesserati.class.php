<?php
if (!defined("_BASE_DIR_")) exit();
include_form('Form');
include_class('Sesso');
include_model('Tesserato','Federazione','Societa');
include_form(FORMELEM_LIST, FORMELEM_AUTOLIST, FORMELEM_CHECK, FORMELEM_STATIC);

class SpostaTesseratiCtrl{
	
	const DA_SOC = 'sorgente';
	const SEL_SOC = 'sorg_selez';
	const A_SOC = 'destinazione';
	const TESS = 'tesserati';
	const SEL_TESS = 'tess_selez';
	
	private $form;
	private $tess;
	
	public function __construct()
	{
		$this->form = new Form('sposta_tess');
		$f = $this->form;
		
		$soc_all = Societa::listaCompleta();
		
		$sorg = new FormElem_List(self::DA_SOC, $f, NULL, true);
		$sorg->setValori($soc_all);
		
		$dest = new FormElem_AutoList(self::A_SOC, $f, NULL, true);
		$dest->setSorgente($sorg, 'soc_comp', array('Societa','ajax_comp'));
		
		new FormElem_Submit('Seleziona', $f, self::SEL_SOC);
		new FormElem_Submit('Sposta', $f, self::SEL_TESS);
		
		if($f->getElem(self::SEL_SOC)->isPremuto())
		{
			$soc_s = $f->getElem(self::DA_SOC)->getValoreRaw();
			$soc_d = $f->getElem(self::A_SOC)->getValoreRaw();
			
			$tes_soc = Tesserato::getInSocieta($soc_s);
			uasort($tes_soc, array('Tesserato','compare'));
			
			$_SESSION['soc_s'] = $soc_s;
			$_SESSION['soc_d'] = $soc_d;
			
			$this->tess = $tes_soc;
			
			foreach($tes_soc as $id_tes=>$tes)
				new FormElem_Check(self::TESS, $f, $id_tes);
		}
		
		if($f->getElem(self::SEL_TESS)->isPremuto())
		{
			$id_soc_s = $_SESSION['soc_s'];
			$id_soc_d = $_SESSION['soc_d'];
			
			foreach($f->getSentKeys(self::TESS) as $id_tes)
			{
				$this->spostaPagamenti($id_tes, $id_soc_s, $id_soc_d);
				TesseratoUtil::get()->cambiaSocieta($id_tes, $id_soc_s, $id_soc_d);
			}
			
			$_SESSION['soc_s'] = NULL;
			$_SESSION['soc_d'] = NULL;
		}
	}
	
	private function spostaPagamenti($id_tes, $id_soc_s, $id_soc_d)
	{
		if(Pagamento::haCorrenti($id_tes))
		{
			foreach(Pagamento::getCorrenti($id_tes) as $pag)
			{
				$pag->setIdSocieta($id_soc_d);
				$pag->salva();
			}
		}
	}
	
	public function getForm()
	{
		return $this->form;
	}
	
	public function getTesserati()
	{
		return $this->tess;
	}
	
	public function getSocieta($id_soc)
	{
		$s = Societa::fromId($id_soc);
		if($s !== NULL)
			return $s->getNomeBreve();
		else return "";
	}
}