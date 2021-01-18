<?php
if (!defined('_BASE_DIR_')) exit();
include_model('Societa');

class SaldoPagamentiCtrl {
	private static $inst = NULL;
	
	private $soc;
	private $pagamenti;
	private $settori_p = array();
	private $settori_np = array();
	private $tipi_p = array();
	private $tipi_np = array();
	private $tesserati_tipo = array();
	private $anni = 0;
	
	public static function get($id_soc) {
		if (self::$inst === NULL || !isset(self::$inst[$id_soc])) {
			self::$inst[$id_soc] = new SaldoPagamentiCtrl($id_soc);
		}
		return self::$inst[$id_soc];
	}
	
	private function __construct($id_soc)
	{
		$this->soc = Societa::fromId($id_soc);
		$this->pagamenti = PagamentoUtil::get()->inSocieta($id_soc);

		$tipi = array();
		$anno_att = date('Y');
		foreach($this->pagamenti as $p)
		{
			/*@var $p Pagamento */
			$anno = $p->getDataScadenza()->getAnno() - $anno_att;
			$anni_arr[$anno] = $anno;
			if($p->getIdSettore() !== NULL)//se non è null allora è un pagamento di un settore
			{
				$id_s = $p->getIdSettore();
				if (!isset($this->settori_p[$anno][$id_s])) {
					$this->settori_p[$anno][$id_s] = 0;
					$this->settori_np[$anno][$id_s] = 0;
				}
				if($p->isPagato())
					$this->settori_p[$anno][$id_s] += $p->getQuotaEuro();
				else 
					$this->settori_np[$anno][$id_s] += $p->getQuotaEuro();
			}
			else //se è null è un pagamento di un tesserato
			{
				$id_t = $p->getIdTipo();
				if (!isset($this->tesserati_tipo[$anno][$id_t])) {
					$this->tipi_p[$anno][$id_t] = 0;
					$this->tipi_np[$anno][$id_t] = 0;
					$this->tesserati_tipo[$anno][$id_t] = 0;
					
					$tipi[$anno][$id_t] = Tipo::fromId($id_t)->getIDSettore();
				}
				
				if($p->isPagato())
				{
					$this->tipi_p[$anno][$id_t] += $p->getQuotaEuro();
					$this->tesserati_tipo[$anno][$id_t] += 1;
				}
				else
				{
					$this->tipi_np[$anno][$id_t] += $p->getQuotaEuro();
					$this->tesserati_tipo[$anno][$id_t] += 1;
				}
			}
		}
		$this->anni = 0;
		if (isset($anni_arr[0])) $this->anni += 1;
		if (isset($anni_arr[1])) $this->anni += 2;
		
		//elimina i tipi senza settore
		foreach ($tipi as $anno => $ta) {
			foreach ($ta as $idt => $ids) {
				if (!(isset($this->settori_p[$anno][$ids]) || isset($this->settori_np[$anno][$ids])))
				{
					unset($this->tipi_p[$anno][$idt]);
					unset($this->tipi_np[$anno][$idt]);
					unset($this->tesserati_tipo[$anno][$idt]);
				}
			}
		}
	}
	
	/**
	 * Esegue una funzione per tutti gli anni disponibili 
	 * @param string $func
	 */
	private function multiAnno($func) {
		if ($this->anni == 3)
			return $this->$func(0) + $this->$func(1);
		else 
			return $this->$func($this->anni - 1);
	}
	
	/**
	 * Restituisce il totale pagato da una società
	 * @return number
	 */
	public function getPagatiTot($anno)
	{
		if ($anno === NULL) 
			return $this->multiAnno('getPagatiTot');
		
		$tot = 0;
		if (isset($this->settori_p[$anno]))
			$tot += array_sum($this->settori_p[$anno]);
		if (isset($this->tipi_p[$anno]))
			$tot += array_sum($this->tipi_p[$anno]);
		
		return $tot;
	}
	
	/**
	 * Restituisce il totale da saldare da una società
	 * @return number
	 */
	public function getScopertiTot($anno)
	{
		if ($anno === NULL) 
			return $this->multiAnno('getScopertiTot');
		
		$tot = 0;
		if (isset($this->settori_np[$anno]))
			$tot += array_sum($this->settori_np[$anno]);
		if (isset($this->tipi_np[$anno]))
			$tot += array_sum($this->tipi_np[$anno]);
		
		return $tot;
	}
	
	/**
	 * Restituisce il totale (pagato e da saldare) di una società
	 * @return number
	 */
	public function getTotaleTot($anno)
	{
		return $this->getPagatiTot($anno)+$this->getScopertiTot($anno);
	}
	
	/**
	 * Indica se sono disponibili le informazioni per un certo settore
	 * @param int $id_sett
	 * @return boolean
	 */
	public function haSettore($anno, $id_sett) {
		return isset($this->settori_p[$anno][$id_sett]);
	}
	
	/**
	 * Restitusce gli id dei settori attivi della società
	 * @return int[]
	 */
	public function getListaSettori($anno) {
		if (isset($this->settori_p[$anno]))
			return array_keys($this->settori_p[$anno]);
		else
			return array();
	}
	
	/**
	 * Restituisce il totale pagato per un settore da una società
	 * @param integer $id_sett
	 * @return number
	 */
	public function getPagatiSett($anno, $id_sett)
	{
		$tot = 0;
		if (isset($this->settori_p[$anno][$id_sett]))
			$tot += $this->settori_p[$anno][$id_sett];
		
		if (isset($this->tipi_p[$anno])) {
			$t_sett = Tipo::getFromSettore($id_sett);
			foreach($t_sett as $idtipo=>$tipo)
			{
				if (isset($this->tipi_p[$anno][$idtipo]))
					$tot += $this->tipi_p[$anno][$idtipo];
			}
		}
		
		return $tot;
	}
	
	/**
	 * Restituisce il totale da pagare per un settore da una società
	 * @param integer $id_sett
	 * @return number
	 */
	public function getScopertiSett($anno, $id_sett)
	{
		$tot = 0;
		if (isset($this->settori_np[$anno][$id_sett]))
			$tot += $this->settori_np[$anno][$id_sett];
		
		if (isset($this->tipi_np[$anno])) {
			$t_sett = Tipo::getFromSettore($id_sett);
			foreach($t_sett as $idtipo=>$tipo)
			{
				if (isset($this->tipi_np[$anno][$idtipo]))
					$tot += $this->tipi_np[$anno][$idtipo];
			}
		}
		
		return $tot;
	}
	
	/**
	 * Restituisce il totale (pagato e da saldare) per un settore da una società
	 * @param integer $id_sett
	 * @return number
	 */
	public function getTotaleSett($anno, $id_sett)
	{
		return $this->getPagatiSett($anno, $id_sett)+$this->getScopertiSett($anno, $id_sett);
	}
	
	/**
	 * Restituisce il totale pagato per l'affiliazione di un settore da una società
	 * @param integer $id_sett
	 * @return number
	 */
	public function getPagatiAff($anno, $id_sett)
	{
		if (isset($this->settori_p[$anno][$id_sett]))
			return $this->settori_p[$anno][$id_sett];
		else
			return 0;
	}
	
	/**
	 * Restituisce il totale da pagare per l'affiliazione di un settore da una società
	 * @param integer $id_sett
	 * @return number
	 */
	public function getScopertiAff($anno, $id_sett)
	{
		if (isset($this->settori_np[$anno][$id_sett]))
			return $this->settori_np[$anno][$id_sett];
		else
			return 0;
	}
	
	/**
	 * Restituisce il totale (pagato e da pagare) per l'affiliazione di un settore da una società
	 * @param integer $id_sett
	 * @return number
	 */
	public function getTotaleAff($anno, $id_sett)
	{
		
		if (isset($this->settori_p[$anno][$id_sett]))
			$p = $this->settori_p[$anno][$id_sett];
		else
			$p = 0;
		if (isset($this->settori_np[$anno][$id_sett]))
			$np = $this->settori_np[$anno][$id_sett];
		else
			$np = 0;
		
		return $p + $np;
	}
	
	/**
	 * Restituisce il totale pagato per un tipo da una società
	 * @param integer $id_tipo
	 * @return number
	 */
	public function getPagatiTipo($anno, $id_tipo)
	{
		if (isset($this->tipi_p[$anno][$id_tipo]))
			return $this->tipi_p[$anno][$id_tipo];
		else
			return 0;
	}
	
	/**
	 * Restituisce il totale da pagare per un tipo da una società
	 * @param integer $id_tipo
	 * @return number
	 */
	public function getScopertiTipo($anno, $id_tipo)
	{
		if (isset($this->tipi_np[$anno][$id_tipo]))
			return $this->tipi_np[$anno][$id_tipo];
		else
			return 0;
	}
	
	/**
	 * Restituisce il totale (pagato e da saldare) per un tipo da una società
	 * @param integer $id_tipo
	 * @return number
	 */
	public function getTotaleTipo($anno, $id_tipo)
	{
		if (isset($this->tipi_p[$anno][$id_tipo]))
			$p = $this->tipi_p[$anno][$id_tipo];
		else
			$p = 0;
		if (isset($this->tipi_np[$anno][$id_tipo]))
			$np = $this->tipi_np[$anno][$id_tipo];
		else
			$np = 0;
		
		return $p + $np;
	}
	
	/**
	 * Restituisce il numero di tesserati di un tipo di una società
	 * @param integer $id_tipo
	 * @return number
	 */
	public function getTesseratiTipo($anno, $id_tipo)
	{
		if (isset($this->tesserati_tipo[$anno][$id_tipo]))
			return $this->tesserati_tipo[$anno][$id_tipo];
		else
			return 0;
	}
	
}