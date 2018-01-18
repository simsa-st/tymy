<?php

namespace Tymy;

use Nette;
use Nette\Utils\Strings;

/**
 * Description of Tymy
 *
 * @author matej
 */
final class Users extends UserAbstraction{
    
    const TAPI_NAME = "users";
    const TSID_REQUIRED = TRUE;
    private $userType;
    
    public function getUserType() {
        return $this->userType;
    }

    public function setUserType($userType) {
        $this->userType = $userType;
        return $this;
    }
    
    public function select() {
        $this->fullUrl .= self::TAPI_NAME;
        if(!is_null($this->userType))
            $this->fullUrl .= "/status/" . $this->userType;
        return $this;
    }

    protected function postProcess(){
        if (($data = $this->getData()) == null)
            return;
        
        $myId = $this->user->getId();
        
        $this->result->menuWarningCount = 0;
        
        $counts = [
            "ALL"=>0,
            "NEW"=>0,
            "PLAYER"=>0,
            "NEW:PLAYER"=>0,
            "MEMBER"=>0,
            "SICK"=>0,
            "DELETED"=>0,
            "INIT"=>0,
            ];
        
        $players = [];
        foreach ($data as $player) {
            $counts["ALL"]++;
            $counts[$player->status]++;
            if(!property_exists($player, "gender")) $player->gender = "UNKNOWN"; //set default value
            if(!property_exists($player, "language")) $player->language = "CZ"; //set default value
            if(!property_exists($player, "canEditCallName")) $player->canEditCallName = true; //set default value
            
            $player->webName = (string)$player->id;
            if(property_exists($player, "fullName")) $player->webName .= "-" . Strings::webalize($player->displayName);
            $this->userWarnings($player);
            $players[$player->id] = $player;
            if($player->id == $myId){
                $this->result->menuWarningCount = $player->errCnt;
                $this->result->me = (object)$player;
                $this->userPermissions($player);
            }
            if(property_exists($player, "lastLogin"))   $this->timeLoad($player->lastLogin);
            $this->timeLoad($player->createdAt);
            if($player->isNew = strtotime($player->createdAt) > strtotime("- 14 days")){
                $counts["NEW"]++;
                if($player->status == "PLAYER")
                    $counts["NEW:PLAYER"]++;
            }
            $players[$player->id] = $player;
        }
        $this->result->data = $players;
        $this->result->counts = $counts;
        
        $this->session->getSection(self::SESSION_SECTION)[$this->getTapiName()] = $this->result;
        
    }
    
    public function reset() {
        $this->userType = NULL;
        return parent::reset();
    }

}