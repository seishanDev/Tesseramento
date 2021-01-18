<?php
if (!defined("_BASE_DIR_")) exit();
include_model('Societa','Comune','Provincia','Regione');
require_once _BASE_DIR_.'fpdf/html2fpdf.php';

class ModelloPrivacy {
	const INDIRIZZO = "Via Gorizia 89 - 27029 Vigevano";
	
	private $soc;
	private $magg;
	private $anno;
	
	/**
	 * @param Societa $soc società o ID
	 * @param bool $magg true per i maggiorenni, false per i minorenni
	 * @param int $anno [opz] anno da stampare nel modello, se omesso utilizza l'anno attuale
	 */
	public function __construct($soc, $magg, $anno=NULL) {
		if (is_object($soc)) 
			$this->soc = $soc;
		else 
			$this->soc = new Societa($soc);
		
		$this->magg = $magg;
		
		if ($anno === NULL)
			$this->anno = date("Y",time());
		else
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

		$pdf->SetXY(40,7);
		$pdf->Rect(130,5,75,20,'D');
		$pdf->SetFont("helvetica","B",9);
		$pdf->Cell(90,4,"FEDERAZIONE ITALIANA ARTI MARZIALI A.S.D.",0,0,'L');
		$pdf->Cell(100,5,"Spazio riservato alla Segreteria",0,1,'L');
		$pdf->SetX(40);
		$pdf->Cell(90,4,self::INDIRIZZO,0,0,'L');
		$pdf->Cell(100,4,"Data Arrivo:",0,1,'L');
		$pdf->SetX(40);
		$pdf->SetFont("helvetica","",9);
		$pdf->Cell(90,4,"Call-Center: 347/1073267",0,0,'L');
		$pdf->Cell(100,4,"Prot. n$deg:",0,1,'L');
		$pdf->SetX(40);
		$pdf->Cell(90,4,"E-mail: tesseramento@fiamsport.it",0,0,'L');
		$pdf->Cell(100,4,"Codice Societ$agrave: ".$this->soc->getCodice(),0,1,'L');
		$pdf->Ln(2);

		$pdf->SetFont("helvetica","B",18);
		$pdf->SetX(40);
		$pdf->Cell(200,10,"TESSERAMENTO ".$this->anno,0,1,'L');
		$pdf->Image(_BASE_DIR_."/img/logo_modulo.jpg",5,5,30);
		$pdf->SetFont("helvetica","",10);
		$pdf->Rect(5,55,200,90,'D');
		$pdf->Rect(5,160,200,70,'D');

		$comune = Comune::fromId($this->soc->getIdComune());
		$prov = Provincia::fromId($comune->getIDProvincia());
		$reg = Regione::fromId($prov->getIDRegione());
		
		$pdf->SetXY(5,40);
		$pdf->SetFillColor(0,0,0);
		$pdf->SetTextColor(255,255,255);
		$pdf->Cell(50,5," Regione: ".utf8_decode($reg->getNome()),1,0,'',1);
		$pdf->Cell(50,5," Provincia: ".utf8_decode($prov->getNome()),1,0,'',1);
		$pdf->Cell(100,5," Societ$agrave: ".utf8_decode($this->soc->getNome()),1,1,'',1);

		$pdf->Ln(3);
		$pdf->SetX(5);
		$pdf->SetFillColor(100,100,100);
		$pdf->Cell(200,6," PRIVACY DICHIARAZIONE DI CONSENSO DEL TESSERATO ",1,'J','C',1);

		$pdf->SetTextColor(0,0,0);
		$pdf->Ln(10);

		$nome_tesserato ="IL/La sottoscritto/a tesserato/a:";
// 		$nome_tesserato .=" $NOME $COGNOME ";
		$nome_tesserato .="                                                                             Nato/a a ";

		$pdf->Cell(180,6,$nome_tesserato,1,1,'L');
		$pdf->Ln(2);

		if ($this->magg) {
			$pdf->Cell(180,5,"",0,1,'L');
		}else{
			$pdf->SetFont("helvetica",'',8);
			$pdf->Cell(40,6,"per il minore di anni 18:",0,0,'R');
			$pdf->Cell(80,6,"","B",0,'L');
			$pdf->SetFont("Helvetica",'I',8);
			$pdf->Cell(60,6,"(Cognome/Nome del GENITORE o TUTORE)",0,1,'L');
		}
		$pdf->Ln(5);

		$pdf->SetFont("helvetica",'',7);

		$pdf->MultiCell(180,3,"Ricevuta e presa visione  dell'informativa sull'utilizzazione dei propri dati personali rilasciata da FIAM ai sensi dell'art.13 del Decreto Legislativo 30 giugno 2003 n. 196, con la firma del presente modulo DICHIARA di dare il proprio consenso",0,'J',0);
		$pdf->Ln(1);
		$pdf->MultiCell(180,3,"A) affinch$eacute la FIAM effettui il trattamento dei propri dati personali cosiddetti \"comuni\" e le comunicazioni e diffusioni ai soggetti e per le finalit$agrave indicate nella predetta informativa; ",0,'J',0);
		$pdf->Ln(1);
		$pdf->MultiCell(180,3,"B) ai sensi dei punti 5 e 6 dell'informativa, affinch$eacute la FIAM effettui il trattamento dei dati personali cosiddetti \"sensibili\", e le comunicazioni e diffusioni ai soggetti e per le finalit$agrave ivi indicate; ",0,'J',0);
		$pdf->Ln(1);
		$pdf->MultiCell(180,3,"C) affinch$eacute la FIAM effettui il trasferimento all'estero dei propri dati personali comuni e sensibili per le finalit$agrave indicate nella predetta informativa. ",0,'J',0);
		$pdf->Ln(15);

		$pdf->Cell(40,5,"Data",0,0,'C');
		$pdf->Cell(75,5,"Firma del Tesserato",0,0,'C');
		if ($this->magg) {
			$pdf->Cell(75,5,"",0,1,'C');
		}else{
			$pdf->Cell(75,5,"Firma  del Genitore o Tutore Legale",0,1,'C');
		}

		$pdf->Cell(40,8,"","B",0,'C');
		$pdf->Cell(75,8,"","B",0,'C');
		$pdf->Cell(75,8,"","B",1,'C');
		$pdf->Ln(1);

		$pdf->Cell(40,5,"",0,0,'C');
		$pdf->Cell(75,5,"",0,0,'C');

		$pdf->SetFont("Helvetica",'I',8);
		if ($this->magg) {
			$pdf->Ln(15);
		}else{
			$pdf->MultiCell(75,4,"TESSERATI MINORENNI (Soggetti che non hanno compiuto i 18 anni): $egrave obbligatoria la firma di un GENITORE o TUTORE LEGALE ",1,"C");
		}

		
		$pdf->SetFont("helvetica",'',10);

		$pdf->Ln(12);
		$pdf->SetX(5);
		$pdf->SetFillColor(100,100,100);
		$pdf->SetTextColor(255,255,255);
		$pdf->Cell(200,6,"LIBERATORIA IMMAGINE DICHIARAZIONE DI CONSENSO DEL TESSERATO",1,'J','C',1);

		$pdf->Ln(10);

		$pdf->SetTextColor(0,0,0);
		$pdf->Cell(180,6,$nome_tesserato,1,1,'L');
		$pdf->Ln(2);

		$pdf->SetFont("helvetica",'',8);
		if ($this->magg) {
			$pdf->Cell(180,5,"",0,1,'L');
			$pdf->Ln(10);
		}else{
			$pdf->Cell(40,6,"per il minore di anni 18:",0,0,'R');
			$pdf->Cell(80,6,"","B",0,'L');
			$pdf->SetFont("Helvetica",'I',8);
			$pdf->Cell(60,6,"(Cognome/Nome del GENITORE o TUTORE)",0,1,'L');
			$pdf->Ln(5);
		}
		$pdf->MultiCell(180,3,"A) Concede l'utilizzo del suo ritratto e autorizza la pubblicazione e diffusione dell'immagine alla FIAM per gli usi consentiti dalla legge",0,'J',0);
		$pdf->Ln(12);

		$pdf->Cell(40,5,"Data",0,0,'C');
		$pdf->Cell(75,5,"Firma del Tesserato",0,0,'C');
		if ($this->magg) {
			$pdf->Cell(75,5,"",0,1,'C');
		}else{
			$pdf->Cell(75,5,"Firma  del Genitore o Tutore Legale",0,1,'C');
		}

		$pdf->Cell(40,8,"","B",0,'C');
		$pdf->Cell(75,8,"","B",0,'C');
		$pdf->Cell(75,8,"","B",1,'C');
		$pdf->Ln(1);

		$pdf->Cell(40,5,"",0,0,'C');
		$pdf->Cell(75,5,"",0,0,'C');

		$pdf->SetFont("Helvetica",'I',8);
		if ($this->magg) {
			$pdf->Ln(10);
		}else{
			$pdf->MultiCell(75,4,"TESSERATI MINORENNI (Soggetti che non hanno compiuto i 18 anni): $egrave obbligatoria la firma di un GENITORE o TUTORE LEGALE ",1,"C");
			$pdf->Ln(3);
		}
		$pdf->Ln(5);

		$TEXT_9="
I Presidenti delle Societ$agrave Sportive sono tenuti a fornire a tutti i tesserati l'informativa di cui al D.Lgs. n.196/2003 in materia di tratamento dei dati personali e acquisire la relativa dichiarazione individuale di consenso, facendo sottoscrivere all'interessato questo modulo da conservare presso la Segreteria Sociale;
I presidenti delle Societ$agrave Sportive, infine, devono sottoscrivere in calce al modello di affiliazione /riaffiliazione la manifestazione del consenso al trattamento dei dati personali della Societ$agrave Sportiva e la dichiarazione di responsabilit$agrave alla sottoscrizione e alla conservazione del consenso al trattamento dei dati personali di tutti i tesserati della Societ$agrave Sportiva.
";

		$pdf->Ln(2);
		$pdf->SetDrawColor(0,0,0);
		$pdf->SetFillColor(200,200,200);
		$pdf->Rect(5,232,200,25,'DF');
		$pdf->SetX(8);
		$pdf->SetFont("Helvetica",'B',8);
		$pdf->Cell(190,4,"Disposizioni in materia di trattamento dei dati personali",'','');
		$pdf->Ln(1);
		$pdf->SetFont("Helvetica",'',7);
		$pdf->MultiCell(190,3,$TEXT_9,'','J');
		$pdf->Ln(12);
		$pdf->SetX(5);
		$pdf->SetFont("Helvetica",'B',7);
		$pdf->Cell(200,5,"F.I.A.M. Federazione Italiana Arti Marziali ASD ",'T',1,'C');
		$pdf->SetFont("Helvetica",'',7);
		$pdf->SetX(5);
		$pdf->Cell(200,5,"Segreteria  Tel.: 347 1073267  ".self::INDIRIZZO." - Ufficio Stampa e Comunicazione: Tel.: 035.270675  Via S. Tomaso,25 - Bergamo",'B',1,'C');

		if ($this->magg)
			$file = "privacy_magg_{$this->anno}.pdf";
		else
			$file = "privacy_minor_{$this->anno}.pdf";
		$pdf->Output($file,'D');
		
		error_reporting($err);
	}
}
