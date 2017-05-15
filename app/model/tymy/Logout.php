<?php

namespace Tymy;

use Nette;
use Nette\Utils\Json;

/**
 * Description of Tymy
 *
 * @author matej
 */
final class Logout extends Tymy{
    
    public function select() {
        return $this;
    }
    
    public function logout(){
        $this->urlStart();

        $this->fullUrl .= "logout";

        $this->urlEnd();
        
        return $this->execute();
    }
    
    protected function tzFields($jsonObj){
        return null;
    }
}