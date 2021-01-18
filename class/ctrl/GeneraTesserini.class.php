<?php

if (!defined("_BASE_DIR_"))
        exit();
include_model('ModelFactory', 'Societa', 'Tesserato', 'Tipo', 'Settore', 'Grado', 'Assicurazione', 'Tesserini');
include_form('Form');
include_form(FORMELEM_CHECK, FORMELEM_NUM);
include_view('QualificaView');

class GeneraTesseriniCtrl {

        const SOC_CH = 'societa_check';
        const GENERA = 'genera';

        private $form;
        private $ar_soc;

        public function __construct() {
                $this->form = new Form('stampa_tesserini');
                $this->ar_soc = Societa::listaCompleta();

                foreach ($this->ar_soc as $soc) {
	     if ($soc->isRinnovata())
	             new FormElem_Check(self::SOC_CH, $this->form, $soc->getId());
	     else
	             unset($this->ar_soc[$soc->getId()]);
                }

                new FormElem_Submit('Genera', $this->form, self::GENERA);

                if ($this->form->isInviato()) {
	     if ($this->form->getElem(self::GENERA)->isPremuto()) {
	             $ar_file = array();

	             foreach ($this->form->getSentKeys(self::SOC_CH) as $id_soc) {
		  $ar_st = $this->stampa($id_soc);
		  $ar_file[$ar_st[0]] = $ar_st[1];
	             }

	             $filename = $this->creaZip($ar_file);

	             $filesize = filesize($filename);
	             if ($filesize == 0) {
		  Log::error('File zip tesserine vuoto', $filename);
		  exit();
	             }

	             $filename_out = 'tesserini_' . date('Y-m-d_H-i-s');
	             header("Content-Disposition: attachment; filename=$filename_out.zip");
	             header("Content-Type: application/zip");
// 				header("Content-length: $filesize\n\n");
	             header("Content-Transfer-Encoding: binary");
	             header('Expires: 0');
	             header('Cache-Control: must-revalidate');

	             readfile($filename);
	     }
                }
        }

        public function stampa($id_soc) {
                $soc = Societa::fromId($id_soc);
                $nome_soc = $soc->getNome();
                $nome_file = $soc->getCodiceAcsi() . "_" . $soc->getNomeBreve() . ".csv";
                $anno = DataUtil::get()->oggi()->getAnno();
                $generato = false;
                if (in_rinnovo()) {
	     $ar = Tesserato::getRinnovatiProssimoAnno($soc->getId());
	     $anno++;
                } else {
	     $ar = Tesserato::getRinnovati($soc->getId());
                }
                $ar_tes = Tesserini::getSocieta($id_soc, $anno);

                $str = "NOME;COGNOME;SOCIETA;DATA NASCITA;SETTORE;QUALIFICA;ANNO;NUMERO TESSERA;;\r\n";

                foreach ($ar as $id_t => $tes) {
	     /* @var $tes Tesserato */
	     $nome = strtoupper($this->cleanString($tes->getNome()));
	     $cognome = strtoupper($this->cleanString($tes->getCognome()));
	     $data_nasc = $tes->getDataNascita()->format('d/m/Y');
	     if (in_rinnovo())
	             $tess = AssicurazioneUtil::get()->getUltimaAssicurazione($tes->getId())->getTessera();
	     else
	             $tess = $tes->getNumTessera();

	     if ($tess == NULL)
	             continue;

	     foreach ($tes->getQualifiche() as $qual) {
	             /* @var $qual Qualifica */
	             $tipo = Tipo::fromId($qual->getIdTipo());

	             if (!$this->isGenerato($ar_tes, $id_t, $tipo->getId(), $anno)) {
		  $settore = Settore::fromId($tipo->getIDSettore());
		  $grado = Grado::fromId($qual->getIdGrado());

		  $n_settore = $settore->getNome();
		  $n_grado = utf8_decode(QualificaViewUtil::get()->getNome($qual));

		  $t = new Tesserini();

		  $t->setAnno($anno);
		  $t->setIDSocieta($id_soc);
		  $t->setIDTesserato($id_t);
		  $t->setIDTipo($tipo->getId());
		  $t->salva();

		  $str .= "$nome;$cognome;$nome_soc;$data_nasc;$n_settore;$n_grado;$anno;$tess;;\r\n";
		  $generato = true;
	             }
	     }
                }

                $cons = $soc->getConsiglio();
                $ruoli = Consiglio::getRuoli();

                foreach ($ruoli as $ruolo) {
	     $t_c = $cons->getMembro($ruolo);
	     if ($t_c !== NULL) {
	             if (!$this->isGenerato($ar_tes, $t_c->getId(), 0, $anno)) {
		  $nome = strtoupper($this->cleanString($t_c->getNome()));
		  $cognome = strtoupper($this->cleanString($t_c->getCognome()));
		  $data_nasc = $t_c->getDataNascita()->format('d/m/Y');
		  $n_ruolo = Consiglio::getRuoloStr($ruolo);

		  if ($ruolo == Consiglio::DIRETTORETECNICO)
		          $tess = $t_c->getNumTessera();
		  else
		          $tess = ";";

		  $t = new Tesserini();

		  $t->setAnno($anno);
		  $t->setIDSocieta($id_soc);
		  $t->setIDTesserato($t_c->getId());
		  $t->setIDTipo(0);
		  $t->salva();

		  $str .= "$nome;$cognome;$nome_soc;$data_nasc;;$n_ruolo;$anno;$tess;;\r\n";
		  $generato = true;
	             }
	     }
                }
                if ($generato) {
	     
	     $fd = fopen("societa_tesserini.csv","w");
	     fwrite($fd, $str);
	     fclose;
	     $path_file_csv = _BASE_DIR_."segr/acsi/societa_tesserini.csv";
	     
	     $path_Email = _BASE_DIR_.'phpmailer/PHPMailerAutoload.php';
	     require_once($path_Email);
	     $mail = new PHPMailer();
	     $mail->CharSet = 'UTF-8';

	     $mail->From = $mail;

	     $mail->FromName ="fiamsport";

	     //$mail->AddAddress('tesseramento@fiamsport.it');

	     $mail->AddAddress('segreteria@fiamsport.it'); //DEBUG
	     $mail->AddAddress('amin1988@hotmail.it');
	     $mail->IsHTML(false);
	     $mail->AddAttachment($path_file_csv); 

	     $mail->Subject = 'Generazione tesserini ';

	     $mail->Body = 'Lista dei tesserini \n ' . $str;



	     if (!$mail->Send()) {

	            // include_class('Log');

	            // Log::warning('email tesserini non inviata', array('id' => $rich->getId(), 'err' => $mail->ErrorInfo, 'obj' => $mail));
	     }
                }

                return array($nome_file, $str);
        }

        private function creaZip($files) {
                $zip = new ZipArchive();

                $filename = tempnam('/tmp', 'acsi');

                if ($zip->open($filename, ZIPARCHIVE::OVERWRITE) !== TRUE) {
	     Log::error("Impossibile aprire il file zip", $filename);
	     exit();
                }

                //riempie lo zip
                foreach ($files as $nome => $cont) {
	     $zip->addFromString($nome, $cont);
                }
                $zip->close();

                return $filename;
        }

        public function cleanString($string) {
                $string = str_replace("à", "a", $string);
                $string = str_replace("á", "a", $string);
                $string = str_replace("â", "a", $string);
                $string = str_replace("ä", "a", $string);

                $string = str_replace("è", "e", $string);
                $string = str_replace("é", "e", $string);
                $string = str_replace("ê", "e", $string);
                $string = str_replace("ë", "e", $string);

                $string = str_replace("ì", "i", $string);
                $string = str_replace("í", "i", $string);
                $string = str_replace("î", "i", $string);
                $string = str_replace("ï", "i", $string);

                $string = str_replace("ò", "o", $string);
                $string = str_replace("ó", "o", $string);
                $string = str_replace("ô", "o", $string);
                $string = str_replace("ö", "o", $string);

                $string = str_replace("ù", "u", $string);
                $string = str_replace("ú", "u", $string);
                $string = str_replace("û", "u", $string);
                $string = str_replace("ü", "u", $string);

                return $string;
        }

        public function isGenerato($ar_tes, $id_tes, $id_tipo, $anno) {
                foreach ($ar_tes as $id_t => $tes) {
	     /* @var $tes Tesserini */
	     if ($tes->getAnno() == $anno)
	             if ($tes->getIDTesserato() == $id_tes)
		  if ($tes->getIDTipo() == $id_tipo)
		          return true;
                }

                return false;
        }

        public function getForm() {
                return $this->form;
        }

        public function getSocieta() {
                return $this->ar_soc;
        }

}