<?php
if (!defined("_BASE_DIR_")) exit();
include_form('Form');
include_model('Utente','Societa');
include_form(FORMELEM_PASSWORD,FORMELEM_LIST, FORMELEM_STATIC);

class GestioneUtentiCtrl{
	
	const USERNAME = 'username';
	const PASSWORD = 'password';
	const PASSWORD_2 = 'password_2';
	const EMAIL = 'email';
	const TIPO = 'tipo';
	const SOCIETA = 'societa';
	const SALVA = 'salva';
	
	private $utenti;
	private $form;
	private $errore;
	
	public function __construct($id_utente)
	{
		$this->utenti = Utente::elenco();
		if($id_utente !== NULL)
		{
			$this->form = new Form('gest_utente');
			$f = $this->form;
			
			if($id_utente == 0) //NUOVO UTENTE
			{
				new FormElem(self::USERNAME, $f, NULL, true, NULL);
				new FormElem_Password(self::PASSWORD, $f, NULL, true, NULL);
				new FormElem_Password(self::PASSWORD_2, $f, NULL, true, NULL);
				new FormElem(self::EMAIL, $f, NULL, true, NULL);
				$tipo = new FormElem_List(self::TIPO, $f, NULL, true, 2);
				$tipo->setValori($this->getTipo());
				$ar_soc = Societa::listaCompleta();
				uasort($ar_soc, array('Societa','compareBreve'));
				$soc = new FormElem_List(self::SOCIETA, $f, NULL, false, NULL);
				$soc->setValori($ar_soc);
				new FormElem_Submit('Salva', $f, self::SALVA);
			}
			else //UTENTE ESISTENTE
			{
				$ut = Utente::fromId($id_utente);
				
				new FormElem_Static(self::USERNAME, $f, NULL, $ut->getUsername());
				new FormElem_Static(self::PASSWORD, $f, NULL, "XXXXXX");
				new FormElem(self::EMAIL, $f, NULL, true, $ut->getEmail());
				$tipo = new FormElem_List(self::TIPO, $f, NULL, true, $ut->getTipo());
				$tipo->setValori($this->getTipo());
				$ar_soc = Societa::listaCompleta();
				uasort($ar_soc, array('Societa','compareBreve'));
				$soc = new FormElem_List(self::SOCIETA, $f, NULL, false, $ut->getIDSocieta());
				$soc->setValori($ar_soc);
				new FormElem_Submit('Salva', $f, self::SALVA);
			}
			
			if($f->isInviatoValido())
				if($f->getElem(self::SALVA)->isPremuto())
				{
					if($id_utente == 0)// se l'utente è nuovo controllo username e password
					{
						if(Utente::isPresente($f->getElem(self::USERNAME)->getValore()))
						{
							$this->errore[self::USERNAME] = 'Username già presente';
						}
						
						if(md5($f->getElem(self::PASSWORD)->getValore()) != md5($f->getElem(self::PASSWORD_2)->getValore()))
						{
							$this->errore[self::PASSWORD] = 'Password diverse';
							$this->errore[self::PASSWORD_2] = 'Password diverse';
						}
						
						$ut = new Utente();
						$ut->setUsername($f->getElem(self::USERNAME)->getValore());
						$ut->setPassword(md5($f->getElem(self::PASSWORD)->getValore()));
					}
					
					$ut->setEmail($f->getElem(self::EMAIL)->getValore());
					$ut->setTipo($f->getElem(self::TIPO)->getValoreRaw());
					if($f->getElem(self::TIPO)->getValoreRaw() == 2)
						$ut->setIDSocieta($f->getElem(self::SOCIETA)->getValoreRaw());
					else 
						$ut->setIDSocieta(NULL);
					
					$ut->salva();
					
					redirect("admin/utenti.php");
				}
		}
	}
	
	private function getTipo()
	{
		return array(1=>"Admin", 2=>"Societ&agrave", 3=>"Segreteria");
	}
	
	public function getForm()
	{
		return $this->form;
	}
	
	public function getErrore($nome)
	{
		if(isset($this->errore[$nome]))
			return $this->errore[$nome];
		else 
			return '';
	}
	
	public function getUtenti()
	{
		return $this->utenti;
	}
	
}