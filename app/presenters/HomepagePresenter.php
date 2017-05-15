<?php

namespace App\Presenters;

use Nette;
use Nette\Application\UI\NavbarControl;
use App\Model;
use Nette\Application\UI\Form;

class HomepagePresenter extends SecuredPresenter {

    public $navbar;
    
    public function startup() {
        parent::startup();
        $this->setLevelCaptions(["0" => ["caption" => "Přehled", "link" => $this->link("Homepage:")]]);
    }

    public function renderDefault() {
        //todo
    }
}