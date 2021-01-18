<?php
if (!defined("_BASE_DIR_")) exit();
include_model('ModelFactory');
include_class('Data');
include_form('Form');
include_form(FORMELEM_NUM,FORMELEM_LIST);

class InsTessACSICtrl {
	
	const NUM_DA = 'num_da';
	const NUM_A = 'num_a';
	const ANNO = 'anno';
	const AGGIUNGI = 'aggiungi';
	
	private $form;
	
	public function __construct()
	{
		$this->form = new Form('ins_tess_ACSI');
		$f = $this->form;
		
		$da = new FormElem_Num(self::NUM_DA, $f, NULL, true);
		$da->isNegValido(false);
		$a = new FormElem_Num(self::NUM_A, $f, NULL, true);
		$a->isNegValido(false);
		
		$anno_oggi = DataUtil::get()->oggi()->getAnno();
		$i = $anno_oggi-1;
		for($i; $i <= $anno_oggi+1; $i++)
			$ar_anno[$i] = $i;
		
		$anno = new FormElem_List(self::ANNO, $f, NULL, true, NULL);
		$anno->setValori($ar_anno);
		
		new FormElem_Submit('Aggiungi', $f, self::AGGIUNGI);
		
		if($f->isInviato())
		{
			if($f->getElem(self::AGGIUNGI)->isPremuto())
			{
				//SALVA NUMERI
				$num_da = $f->getElem(self::NUM_DA)->getValore();
				$num_a = $f->getElem(self::NUM_A)->getValore();
				
				$anno_sel = $f->getElem(self::ANNO)->getValoreRaw();
				
				$agg = 0;
				
				for ($i=$num_da; $i<=$num_a; $i++)
				{
					if($this->insTess($i,$anno_sel))
						$agg ++;
				}
				
				$_SESSION["NUM_TESS_AGG"] = $agg;
				ricarica();
			}
		}
	}
	
	public function getForm()
	{
		return $this->form;
	}
	
	/**
	 * 
	 * @param int $num_tess
	 * @param Database $db
	 */
	public function insTess($num_tess, $anno)
	{
		$db = Database::get();
		return $db->insert('assicurazioni_invii', array('tessera'=>$num_tess, 'idtesserato'=>NULL, 'ts'=>NULL, 'anno'=>$anno));
	}
	
}