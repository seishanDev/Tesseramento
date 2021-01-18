<?php
if (!defined("_BASE_DIR_")) exit();
include_model('ModelFactory','Societa');
include_form('Form');
include_form(FORMELEM_CHECK, FORMELEM_FILE);


class ComunicazioneCtrl {
	const EMAIL_CH = 'email_check';
	const SUBJECT = 'subject';
	const BODY = 'body';
	const INVIA = 'invia';
	const ATTCH_ITER = 'attachment_';
	const ATTCH_1 = 'attachment_1';
	const ATTCH_2 = 'attachment_2';
	const ATTCH_3 = 'attachment_3';
	const ATTCH_4 = 'attachment_4';
	const ATTCH_5 = 'attachment_5';
	
	private $form;
	private $ar_soc;
	
	public function __construct()
	{
		$this->form = new Form('comunicazione');
		$f = $this->form;
		
		$this->ar_soc = Societa::listaCompleta();
		uasort($this->ar_soc, array('Societa','compareFull'));
		
		foreach($this->ar_soc as $id_s=>$soc)
		{
			if($soc->isRinnovata())
				new FormElem_Check(self::EMAIL_CH, $f, $id_s, true);
			else 
				unset($this->ar_soc[$id_s]);
		}
		
		new FormElem(self::SUBJECT, $f);
		new FormElem(self::BODY, $f);
		new FormElem_File(self::ATTCH_1, $f);
		new FormElem_File(self::ATTCH_2, $f);
		new FormElem_File(self::ATTCH_3, $f);
		new FormElem_File(self::ATTCH_4, $f);
		new FormElem_File(self::ATTCH_5, $f);
		new FormElem_Submit('Invia', $f, self::INVIA);
		
		if($f->isInviato())
		{
			if($f->getElem(self::INVIA)->isPremuto())
			{
				require _BASE_DIR_.'phpmailer/PHPMailerAutoload.php';
				
				$email = new PHPMailer();
				
				$email->isSMTP();                                       // Set mailer to use SMTP
				$email->Host = 'smtp.aruba.it';                         // Specify main and backup server
// 				$mail->SMTPAuth = true;                                 // Enable SMTP authentication
// 				$mail->Username = 'tesseramento@fiamsport.it';          // SMTP username
// 				$mail->Password = 'FIAM9135';							// SMTP password
// 				$mail->SMTPSecure = 'ssl';                              // Enable encryption, 'ssl' also accepted
				
				$email->From = 'segreteria@fiamsport.it';
				$email->FromName = 'Segreteria FIAM';
				$email->addAddress('segreteria@fiamsport.it','Segreteria FIAM');
				$email->Subject = $f->getElem(self::SUBJECT)->getValore();
				$email->isHTML(true);
				$email->Body = $f->getElem(self::BODY)->getValore();
				
				foreach($f->getSentKeys(self::EMAIL_CH) as $id_s=>$str)
				{
					$soc = Societa::fromId($str);
					
					if($soc !== NULL)
					{
						$email->addBCC($soc->getEmail(), $soc->getNomeBreve());
					}
				}
				
				for($i=1; $i<6; $i++)
				{
					$doc = $f->getElem(self::ATTCH_ITER.$i);
					if($doc->getValore() !== NULL && $doc->getValore() != '')
					{
						$email->addAttachment($doc->getValore(), $doc->getNomeFile());
					}
				}
				
				$email->addBCC('supporto@fiamsport.it', 'Supporto FIAM');//CONTROLLO COMUNICAZIONI
				
				if(!$email->send())
				{
					$err = $email->ErrorInfo;
					echo "<script type='text/javascript'>alert('Errore! $err');</script>";
				}
				else 
				{
					echo "<script type='text/javascript'>alert('Comunicazione inviata correttamente');</script>";
				}
			}
		}
		
	}
	
	public function getForm()
	{
		return $this->form;
	}
	
	public function getSocieta()
	{
		return $this->ar_soc;
	}
	
}
	