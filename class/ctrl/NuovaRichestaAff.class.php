<?php

if (!defined("_BASE_DIR_")) exit();

include_controller('FormAffiliazione');

include_model('RichiestaAff','Comune');



class NuovaRichiestaAffCtrl {

	private $err = array();

	private $form;

	private $salvato = false;

	

	public function __construct()

	{

		$this->form = new FormAffiliazione(new RichiestaAff(), false, 'richiesta_affiliazione');

		$this->form->addSubmit('Inserisci');

		

		

		if($this->form->isInviato())

		{

			if($this->form->isValido())

			{

				$rich = $this->form->getRichiesta();

				if ($rich->salva()) {

					$this->salvato = true;

					

					Log::info('nuova richiesta affiliazione', 

						array('id'=>$rich->getId(), 'nome'=>$rich->getNome()));

					

					//TODO generalizzare o portare fuori?

					$err_lv = error_reporting(0);
                                        $path_Email = _MAILER_INCLUDE_;
					require_once($path_Email);

					$mail = new PHPMailer();

					//$mail->IsSMTP();                    // attiva l'invio tramiteSMTP

					//$mail->Host= '$SMTP'; // indirizzo smtp

					$mail->CharSet = 'UTF-8';

					$mail->From = $rich->getEmail();

					$mail->FromName = $rich->getNome();

					$mail->AddAddress('tesseramento@fiamsport.it');

					//$mail->AddAddress('flavio.debene@gmail.com'); //DEBUG
                                        $mail->AddAddress('amin1988@hotmail.it');

					$mail->IsHTML(false);

					$mail->Subject  =  'NUOVA AFFILIAZIONE '.$rich->getId();

					$mail->Body     =  'Nuova richiesta di affiliazione da parte di '.$rich->getNome();

					

					if (!$mail->Send()) {

						include_class('Log');

						Log::warning('email affiliazione non inviata',

							array('id'=>$rich->getId(), 'err'=>$mail->ErrorInfo, 'obj'=>$mail));

					}

					error_reporting($err_lv);

				}

			}

			else 

			{

				//TODO generalizzare

				foreach ($this->form->getErrori() as $nome => $err) 

				{

					switch ($err) 

					{

						case FORMERR_OBBLIG:

							$msg = 'Campo obbligatorio';

							break;

						case FORMERR_FORMAT:

							$msg = 'Formato non valido';

							break;

						case FORMERR_DATA_MAX:

							$msg = 'La data dev\'essere nel passato';

							break;

						default:

							$msg = "Errore $err";

							break;

					}

					$this->err[$nome] = $msg;

				}

			}

			

		}

	}

	

	public function getForm()

	{

		return $this->form;

	}

	

	public function getErrore($nome) {

		if (isset($this->err[$nome]))

			return $this->err[$nome];

		else

			return '';

	}

	

	public function isSalvato() {

		return $this->salvato;

	}

}