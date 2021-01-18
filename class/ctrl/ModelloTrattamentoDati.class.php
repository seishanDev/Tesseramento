<?php
if (!defined("_BASE_DIR_")) exit();
include_model('Societa','Comune','Provincia','Regione','Tesserato');
require_once _BASE_DIR_.'fpdf/html2fpdf.php';

class ModelloTrattamentoDati {
	const INDIRIZZO = "Via Gorizia 89 - 27029 Vigevano";
	
	/**
	 * @var Societa
	 */
	private $soc;
	private $anno;
	
	/**
	 * @param Societa $soc società o ID
	 * @param int $anno [opz] anno da stampare nel modello, se omesso utilizza l'anno attuale
	 */
	public function __construct($soc, $anno=NULL) {
		if (is_object($soc)) 
			$this->soc = $soc;
		else 
			$this->soc = new Societa($soc);
		
		if ($anno === NULL) {
			$this->anno = date("Y",time());
			if (in_rinnovo()) $this->anno++;
		} else
			$this->anno = $anno;
	}
	
	public function stampa() {
		$err = error_reporting(0);
		
		//caratteri speciali
		$deg = chr(176);
		$agrave = chr(224);
		$eacute = chr(233);
		$egrave = chr(232);
		
		
		$pdf=new HTML2FPDF();
		$pdf->AddPage();
		
		//intestazione
		$pdf->SetXY(40,7);
		$pdf->Rect(130,5,75,20,'D');
		$pdf->SetFont("helvetica","B",9);
		$pdf->Cell(90,4,"FEDERAZIONE ITALIANA ARTI MARZIALI A.S.D.",0,0,'L');
		$pdf->Cell(100,5,"Spazio riservato alla Segreteria",0,1,'L');
		$pdf->SetX(40);
		$pdf->Cell(90,4, self::INDIRIZZO,0,0,'L');
		$pdf->Cell(100,4,"Data Arrivo:",0,1,'L');
		$pdf->SetX(40);
		$pdf->SetFont("helvetica","",9);
		$pdf->Cell(90,4,"Cell: 347/1073267 - Fax 0381/349197",0,0,'L');
		$pdf->Cell(100,4,"Prot. n$deg:",0,1,'L');
		$pdf->SetX(40);
		$pdf->Cell(90,4,"E-mail: tesseramento@fiamsport.it",0,0,'L');
		$pdf->Cell(100,4,"Codice Societ$agrave: ".$this->soc->getCodice(),0,1,'L');
		$pdf->Ln(10);

		//box società
		$pdf->SetFont("helvetica","B",10);
		$pdf->Cell(200,5,"DICHIARAZIONE DI CONSENSO AL TRATTAMENTO DEI DATI PERSONALI",0,1,'C');
		$pdf->Cell(200,5,"TESSERAMENTO ATLETI $this->anno",0,1,'C');
		$pdf->Image(_BASE_DIR_."/img/logo_modulo.jpg",5,5,30);
		$pdf->SetFont("helvetica","",9);
		$pdf->SetFillColor(0,0,0);
		$pdf->SetDrawColor(200,200,200);
		$pdf->Rect(5,45,200,15,'F');
		$pdf->SetFillColor(255,255,255);
		$pdf->Rect(6,46,198,13,'DF');
		$pdf->SetFillColor(0,0,0);
		$pdf->SetTextColor(255,255,255);
		$pdf->SetXY(5,40);
		$pdf->SetTextColor(0,0,0);
		$pdf->Ln(7);
		$pdf->Cell(100,5," Societ$agrave: ".utf8_decode($this->soc->getNome()),"B",'','');

		$com = Comune::fromId($this->soc->getIdComune());
		$prov = Provincia::fromId($com->getIDProvincia());
		$reg = Regione::fromId($prov->getIDRegione());
		
		$pdf->Cell(40,5," Regione: ".utf8_decode($reg->getNome()),"B",'','');
		$pdf->Cell(40,5," Provincia: ".utf8_decode($prov->getNome()),"B",1,'');

		$sede_legale = utf8_decode($this->soc->getSedeLegale());
		$cap = utf8_decode($this->soc->getCAP());
		$comune = utf8_decode($com->getNome());
		$pdf->Cell(180,5," Sede legale: $sede_legale - $cap $comune","B",'','');
		$pdf->Ln(10);
		$pdf->SetFontSize(7);
		
		$pos_1_int=69;
		
		//elenco tesserati
		$tesslist = TesseratoUtil::get()->getAttivi($this->soc->getId(), $this->anno);
		uasort($tesslist, array('Tesserato','compare'));
		$i=1;
		foreach ($tesslist as $tes) {
			$nome = utf8_decode($tes->getCognome() . ' '. $tes->getNome());
			$data_nascita = $tes->getDataNascita()->toDMY();
			$luogo_nascita = utf8_decode($tes->getLuogoNascita());
			$residenza = utf8_decode($tes->getCittaRes().' - '. $tes->getIndirizzo());

			$pdf->SetX(10);
			$pdf->Cell(10,9,"  $i",1,0,'L');

			$pdf->SetTextColor(255,255,255);
			$pdf->SetFillColor(100,100,100);
			$pdf->SetX(20);
			$pdf->Cell(60,4,"Nome Cognome",'B',0,'L',1);
			$pdf->Cell(30,4,"Data di Nascita",'B',0,'L',1);
			$pdf->Cell(40,4,"Luogo di nascita",'B',0,'L',1);
			$pdf->Cell(50,4,"Residente in",'B',1,'L',1);

			$pdf->SetTextColor(0,0,0);
			$pdf->SetX(20);
			$pdf->Cell(60,5,$nome,1,0,'L');
			$pdf->Cell(30,5,$data_nascita,1,0,'L');
			$pdf->Cell(40,5,$luogo_nascita,1,0,'L');
			$pdf->Cell(50,5,$residenza,1,1,'L');
			$pdf->Ln(2);

			$i++;
		}
		$pdf->Ln(2);
		$pdf->Rect(5,242,200,10);
		$pdf->SetFontSize(7);
		$pdf->MultiCell(190,3,"Il/La sottoscritto/a dichiara sotto la propria responsabilit$agrave ".
				"che ha provveduto a formalizzare la posizione sanitaria dei tesserati di cui sopra ".
				"come prescritto dalla Legge e di custodire presso la Segreteria Sociale la documentazione ".
				"relaiva",0,'J');
		$pdf->Ln(3);
		$pdf->SetX(5);
		$pdf->SetFontSize(8);
		
		//nota finale
		$pdf->Cell(200,4,"MANIFESTAZIONE DI CONSENSO AL TRATTAMENTO DEI DATI PERSONALI",0,1,'C',1);
		$pdf->SetFontSize(7);
		$pdf->Ln(1);
		$pdf->MultiCell(190,3,"Il/La sottoscritto/a, in qualit$agrave di Legale rappresentante della ".
				"Societ$agrave Sportiva, dichiara di aver fornito l'informativa di cui all'Art.13 del ".
				"D.Lgs. n.196/2003 agli Atleti di cui sopra e di avere raccolto il loro consenso al ".
				"trattamento dei dati personali anche sensibili e di custodirli presso la Sede Sociale",0,'J');
		$pdf->Ln(10);

		$pdf->Cell(10,3,"Data ",0,0,'R');
		$pdf->Cell(50,3," ",'B',0,'R');

		$pdf->Cell(20,3,"Il Presidente ",0,0,'R');
		$pdf->Cell(100,3," ",'B',0,'R');

		$pdf->Ln(8);

		$sped = "SPEDIRE IL MODULO COMPLETO A: FEDERAZIONE ITALIANA ARTI MARZIALI A.S.D. ".self::INDIRIZZO;
		$pdf->Ln(5);
		$pdf->SetFont("helvetica",'B',7);
		$pdf->MultiCell(180,3,$sped,1,'C');
		
		$file = 'trattamento_dati_'.date('Y-m-d').'.pdf';
		$pdf->Output($file,'D');
		
		error_reporting($err);
	}
}