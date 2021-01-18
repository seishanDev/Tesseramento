<?php

include_model('Settore', 'Comune', 'Provincia', 'Regione', 'Federazione');

include_form('Form');

include_form(FORMELEM_LIST, FORMELEM_DATE, FORMELEM_CHECK, FORMELEM_STATIC, FORMELEM_AUTOLIST);

class FormAffiliazione extends Form
{

    const ID_REG = 'regione';
    const ID_PROV = 'prov';
    const ID_COMUNE = 'comune';
    const FEDER = 'federazione';
    const NOME = 'nome';
    const NOME_BREVE = 'nomebreve';
    const DATA_COST = 'data_cost';
    const P_IVA = 'p_iva';
    const SEDE_LEG = 'sede_legale';
    const CAP = 'cap';
    const TEL = 'tel';
    const FAX = 'fax';
    const EMAIL = 'email';
    const WEB = 'web';
    const DATA_INS = 'data_inserimento';
    const SETTORE = 'settore';

    /**

     * 

     * @var RichiestaAff

     */
    private $rich;
    private $settori = array();

    /**

     * @param RichiestaAff $rich

     * @param bool $admin

     * @param string $nome

     */
    public function __construct($rich, $admin, $nome)
    {

        parent::__construct($nome);

        $this->rich = $rich;

        $this->addElems($admin);
    }

    /**

     * Inserisce nel form gli elementi necessari per la creazione di una richiesta d'affiliazione

     */
    private function addElems($admin)
    {

        $idprov = NULL;

        $idreg = NULL;

        $idcom = $this->rich->getIDComune();

        if ($idcom !== NULL)
        {

            $o = Comune::fromId($idcom);

            if ($o !== NULL)
            {

                $idprov = $o->getIDProvincia();

                $o = Provincia::fromId($idprov);

                if ($o !== NULL)
                    $idreg = $o->getIDRegione();
            }
        }

        $reg = new FormElem_List(self::ID_REG, $this, NULL, true, $idreg);

        $reg->setValori(Regione::listaCompleta());

        $prov = new FormElem_AutoList(self::ID_PROV, $this, NULL, true, $idprov);

        $prov->setSorgente($reg, 'province', array('Provincia', 'ajax'));

        $id_c = new FormElem_AutoList(self::ID_COMUNE, $this, NULL, true, $idcom);

        $id_c->setSorgente($prov, 'comuni', array('Comune', 'ajax'));



        if ($this->rich->esiste())
        {

            $fed = new FormElem_List(self::FEDER, $this, NULL, true, $this->rich->getIDFederazione());

            $fed->setValori(Federazione::elenco());
        } else
        {

            $fed = new FormElem_List(self::FEDER, $this, NULL, true, 1);

            $fed->setValori(Federazione::elenco());
        }



        new FormElem(self::NOME, $this, NULL, true, $this->rich->getNome());

        new FormElem(self::NOME_BREVE, $this, NULL, $admin, $this->rich->getNomebreve());

        new FormElem_Data(self::DATA_COST, $this, NULL, true, $this->rich->getDataCost());

        new FormElem(self::P_IVA, $this, NULL, true, $this->rich->getPIva());

        new FormElem(self::SEDE_LEG, $this, NULL, true, $this->rich->getSedeLegale());

        new FormElem(self::CAP, $this, NULL, true, $this->rich->getCap());

        new FormElem(self::TEL, $this, NULL, true, $this->rich->getTel());

        new FormElem(self::FAX, $this, NULL, false, $this->rich->getFax());

        new FormElem(self::EMAIL, $this, NULL, true, $this->rich->getEmail());

        new FormElem(self::WEB, $this, NULL, false, $this->rich->getWeb());



        foreach (Settore::elenco() as $idsett => $sett)
        {
            $settore_attivo = $sett->getAttivo();
            if (empty($settore_attivo))
            {
                continue;
            }
            new FormElem_Check(self::SETTORE, $this, $idsett, $this->rich->haSettore($idsett));
        }
    }

    protected function checkValidita($valido)
    {

        $sel = false;

        foreach (Settore::elenco() as $idsett => $sett)
        {

            $settore_attivo = $sett->getAttivo();
            if (empty($settore_attivo))
            {
                continue;
            }
            $el = $this->getElem(self::SETTORE, $idsett);

            if ($el->getValore())
            {

                $sel = true;

                break;
            }
        }

        if (!$sel)
        {

            $this->err[self::SETTORE] = FORMERR_OBBLIG;
        }

        return $sel;
    }

    /**

     *

     * @return RichiestaAff

     */
    public function getRichiesta()
    {

        $rich = $this->rich;



        $rich->setIDComune($this->getElem(self::ID_COMUNE)->getValore()->getId()); //TODO

        $rich->setIDFederazione($this->getElem(self::FEDER)->getValore()->getId());

        $rich->setNome($this->getElem(self::NOME)->getValore());

        $rich->setNomebreve($this->getElem(self::NOME_BREVE)->getValore());

        $rich->setDataCost($this->getElem(self::DATA_COST)->getValore());

        $rich->setPIva($this->getElem(self::P_IVA)->getValore());

        $rich->setSedeLegale($this->getElem(self::SEDE_LEG)->getValore());

        $rich->setCap($this->getElem(self::CAP)->getValore());

        $rich->setTel($this->getElem(self::TEL)->getValore());

        $rich->setFax($this->getElem(self::FAX)->getValore());

        $rich->setEmail($this->getElem(self::EMAIL)->getValore());

        $rich->setWeb($this->getElem(self::WEB)->getValore());



        $id_fed = $this->getElem(self::FEDER)->getValore()->getId();



        foreach (Settore::elenco() as $idsett => $sett)
        {
            $settore_attivo = $sett->getAttivo();
            if (empty($settore_attivo))
            {
                continue;
            }

            $chkset = $this->getElem(self::SETTORE, $idsett);

            if ($chkset->getValore())
            {

                if ($this->checkSettFeder($id_fed, $idsett))
                    $this->settori[] = $idsett;
            }
        }

        $rich->setSettori($this->settori);



        return $rich;
    }

    private function checkSettFeder($id_fed, $id_sett)
    {

        if ($id_fed != Federazione::FEDER_ETSIA)
        {

            if ($id_sett != 4)
                return true;
            else
                return false;
        }



        if ($id_fed == Federazione::FEDER_ETSIA)
        {

            if ($id_sett != 1)
                return true;
            else
                return false;
        }
    }

}
