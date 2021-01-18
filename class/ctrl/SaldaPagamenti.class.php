<?php
if (!defined('_BASE_DIR_')) exit();
include_model('Pagamento');
include_form('Form');
include_form(FORMELEM_DEC);

class SaldaPagamentiCtrl {
	const F_PAGATO = 'pagato';
	
	private $tot;
	private $form;
	
	public function __construct($idsoc) {
		$this->tot = PagamentoUtil::get()->getTotale($idsoc);
		if ($this->tot === NULL) $this->tot = 0;
		
		$f = new Form('saldo');
		$el = new FormElem_Decimale(self::F_PAGATO, $f, NULL, true);
		$el->setNegValido(false);
		$el->setZeroValido(false);
		$this->form = $f;
		
		if ($f->isInviatoValido() && $el->getValore()*100 == $this->tot) {
			//ha pagato tutto
			$pag = PagamentoUtil::get()->nonPagati($idsoc);
			$saldo = $this->tot;
			foreach ($pag as $p) {
				$saldo -= $p->getQuota();
				if ($saldo < 0) {
					$saldo += $p->getQuota();
					break;
				}
				$p->setPagato();
				$p->salva();
			}
			Log::info('inserito pagamento',array('idsocieta'=>$idsoc, 'quota'=>($this->tot-$saldo)/100));
			$this->tot = $saldo;
			
			$f->setPersistente(false);
		}
	}
	
	public function getForm() {
		return $this->form;
	}
	
	public function getTotale() {
		return $this->tot / 100;
	}
}