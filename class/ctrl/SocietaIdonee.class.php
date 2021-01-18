<?php

if (!defined("_BASE_DIR_")) exit();

include_model('ModelFactory','Societa','Tesserato');

include_class('Data');

include_form('Form');

include_form(FORMELEM_CHECK, FORMELEM_NUM);



class SocietaIdoneeCtrl {

	

	private $idonee;

	private $consiglio;

	

	public function __construct()

	{

		$db = Database::get();

		$anno = DataUtil::get()->oggi()->getAnno();

		$anno --;

		$str_data = "$anno-12-31";

		//$rs = $db->select('pagamenti',"idtesserato IS NULL AND scadenza >= '$str_data' GROUP BY idsocieta","count(idpagamento), idsocieta");
		//$rs = $db->select('pagamenti',"idtesserato IS NULL AND scadenza >= '$str_data' GROUP BY idsocieta","count(idpagamento), idsocieta");
                $sql = " SELECT count(pag.idpagamento) as tot_pag, pag.idsocieta "
                        . "  FROM pagamenti as pag INNER  JOIN societa as s ON s.idsocieta = pag.idsocieta "
                        . " WHERE  DATE_ADD(data_inserimento, INTERVAL 2 year) < NOW() AND pag.idtesserato IS NULL AND pag.scadenza >='$str_data' "
                        . " GROUP BY pag.idsocieta  ORDER BY pag.idsocieta ASC";
                $rs = $db->query($sql);
		

		$pagamenti = array();

		while($row = $rs->fetch_row())

		{

			$pagamenti[$row[1]] = $row[0];

		}

		

		$idonee = array();

		foreach($pagamenti as $idsocieta=>$num_pag)

		{

			if($num_pag >= 2)

				$idonee[$idsocieta] = Societa::fromId($idsocieta);

		}

		$this->idonee = $idonee;

		

		$consiglio = array();

		foreach($idonee as $idsocieta=>$societa)

		{

                    if ( empty($societa))
                    {
                        $ff= "";
                        continue;
                    }
			$cons = $societa->getConsiglio();
                        if ($idsocieta == 34)
                        {
                            $r = 2 ;
                        }

			$cons_soc = array();

			$i = 1;

		

			foreach(Consiglio::getRuoli() as $ruolo)

			{

				if($ruolo == Consiglio::DIRETTORETECNICO)

					continue;

				$str = Consiglio::getRuoloStr($ruolo);

				if($str == Consiglio::getRuoloStr(Consiglio::CONSIGLIERE1))

				{

					$str .= " $i";

					$i++;

				}

				$membro = $cons->getMembro($ruolo);

				if($membro !== NULL)

					$cons_soc[$str] = $membro->__toStringConsiglio();

				else

					$cons_soc[$str] = "<i class=\"icon-minus\"></i>";

			}

			

			$consiglio[$idsocieta] = $cons_soc;

		}

		

		$this->consiglio = $consiglio;

	}

	

	public function getIdonee()

	{

		return $this->idonee;

	}

	

	public function getConsiglio()

	{

		return $this->consiglio;

	}

}