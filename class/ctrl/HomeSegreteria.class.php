<?php
if (!defined('_BASE_DIR_')) exit();
include_model('Assicurazione','Tesserato','Societa');
include_class('Sesso');

class HomeSegreteriaCtrl {
	
	private $assicurazione;
	private $tesserati;
	private $societa;
	private $pagamenti;
	
	public function __construct()
	{
		$this->tesserati = TesseratoUtil::get()->getLast(10);
		$this->societa = SocietaUtil::get()->getLast(10);
	}
	
	public function getTesserati()
	{
		return $this->tesserati;
	}
	
	public function getSocieta()
	{
		return $this->societa;
	}
}