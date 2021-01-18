<?php

if (!defined("_BASE_DIR_")) exit();

include_model('RichiestaAff','Societa');

include_controller('FormAffiliazione');



class ModificaRichiestaAffCtrl {

	

	const SUBMIT_ACCETTA = 'accetta';

	const SUBMIT_RIFIUTA = 'rifiuta';

	const BASE_CODICE = 98000;

	

	/**

	 * 

	 * @var FormAffiliazione

	 */

	private $form;

	private $rich;

	private $id_rich;

	

	public function __construct($id_rich, $callback)

	{

		$this->rich = new RichiestaAff($id_rich);

		$this->id_rich = $id_rich;

		

		if(!$this->rich->esiste()) 

		{

			go_home();

		}

		

		$f = new FormAffiliazione($this->rich, true, 'modifica_richiesta');

		$this->form = $f;

		

		new FormElem_Submit('Accetta', $f, self::SUBMIT_ACCETTA);

		new FormElem_Submit('Rifiuta', $f, self::SUBMIT_RIFIUTA);

		

		if($f->getElem(self::SUBMIT_RIFIUTA)->isPremuto())

		{

			$this->rich->elimina();

			

			if(is_callable($callback))

				call_user_func($callback,self::SUBMIT_RIFIUTA);

		}

		elseif($f->isInviato())

		{

			if($f->isValido())

			{

				

				if($f->getElem(self::SUBMIT_ACCETTA)->isPremuto())

				{

					$this->rich = $f->getRichiesta();

					$soc = new Societa();

					

					$this->richToSoc($this->rich, $soc);

					

					if($soc->salva())

					{

						$this->rich->elimina();

						$this->creaUtente($soc);

					

						if(is_callable($callback))

							call_user_func($callback,self::SUBMIT_ACCETTA);

					}

					

					redirect('listarichaff.php');

				}

				

				

			}

			else

			{

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

	

	/**

	 * Copia i dati dalla richiesta alla società

	 * @param RichiestaAff $rich

	 * @param Societa $soc

	 */

	public function richToSoc($rich, $soc)

	{

		$soc->setIDComune($rich->getIDComune());

		$cod = $rich->getId();

		//$cod += self::BASE_CODICE;
                
                $cod = $soc->getLastCodice() + 1 ;

		$soc->setCodice($cod);

		$soc->setIdFederazione($rich->getIDFederazione()); 

		$soc->setNome($rich->getNome());

		$soc->setNomebreve($rich->getNomebreve());

		$soc->setDataCostituzione($rich->getDataCost());

		$soc->setPIva($rich->getPIva());

		$soc->setSedeLegale($rich->getSedeLegale());

		$soc->setCap($rich->getCap());

		$soc->setTel($rich->getTel());

		$soc->setFax($rich->getFax());

		$soc->setEmail($rich->getEmail());

		$soc->setSito($rich->getWeb());

		

		foreach ($rich->getSettori() as $idsett)

			$soc->aggiungiSettore($idsett);

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

	

	/**

	 * Crea l'utente di default legato alla società

	 * @param Societa $soc

	 */

	private function creaUtente($soc) {

		include_model('Utente');

		include_class('Password');

		$psw = Password::get()->genera($soc->getId());

		$u = Utente::creaSoc($soc, $psw);

		if ($u === NULL) {

			Log::warning('Utente non creato', array('idsocieta'=>$soc->getId(), 'codice'=>$soc->getCodice()));

			return;

		}

		

		if (!$u->salva()) {

			$u->logValori(E_ERROR, 'Errore nel salvataggio dell\'utente', array('err'=>$u->getErrore()));

			return;

		}

		

		//inviare email con dati d'accesso

		$nome = $soc->getNome();

		$uname = $u->getUsername();

		$msg = "Gentile Società $nome,\r\nla ringraziamo per l'avvenuta affiliazione.\r\nDi seguito trova i codici di accesso per poter effettuare l'inserimento dei tesserati.\r\n\r\n"

				."Username: $uname\r\nPassword: $psw\r\n\r\n"

				."Per eventuali problemi e / o comunicazioni, può contattare la segreteria FIAM all'indirizzo segreteria@fiamsport.it oppure al numero di telefono 347 1073267, "

				."dal lunedì al venerdì dalle 8.30 alle 12.30";

		

		$err = error_reporting(0);

		require_once(_MAILER_INCLUDE_);

		$mail = new PHPMailer();

		$mail->CharSet = 'UTF-8';

		$mail->From = 'tesseramento@fiamsport.it';

		$mail->FromName = 'FIAM';

		$mail->AddAddress($u->getEmail());

		$mail->IsHTML(false);

		$mail->Subject  =  '[Tesseramento FIAM] Avvenuta affiliazione';

		$mail->Body     =  $msg;

		

		if (!$mail->Send()) {

			Log::warning('email utente non inviata',

				array('id'=>$u->getId(), 'err'=>$mail->ErrorInfo, 'obj'=>$mail));

		}

		error_reporting($err);

	}

}