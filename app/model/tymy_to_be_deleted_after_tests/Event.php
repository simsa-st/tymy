<?php

namespace Tymy;

use Nette;

/**
 * Description of Tymy
 *
 * @author matej
 */
final class Event extends Tymy{
    
    const TAPI_NAME = "event";
    const TSID_REQUIRED = TRUE;
    
    public function select() {
        if (!isset($this->recId))
            throw new Exception\APIException('Event ID not set!');
        
        $this->fullUrl .= self::TAPI_NAME . "/" .$this->recId;
        
        return $this;
    }
    
    public function edit($fields){
        if (!isset($this->recId))
            throw new \Tapi\Exception\APIException('User ID not set!');
        if (!$fields)
            throw new \Tapi\Exception\APIException('Fields to edit not set!');
        if (!$this->user->isAllowed("SYS","EVE_UPDATE"))
            throw new \Tapi\Exception\APIException('Permission denied!');
        
        
        $this->urlStart();

        $this->fullUrl .= self::TAPI_NAME . "/" .$this->recId;

        $this->urlEnd();
        
        $this->method = "PUT";
        
        foreach ($fields as $key => $value) {
            if(in_array($key, ["startTime","endTime","closeTime"]))
                $this->timeSave($value);
        }
        
        $this->setPostData((object)$fields);
        
        $this->result = $this->execute();
        return $this;
    }
    
    public function delete(){
        if (!isset($this->recId))
            throw new \Tapi\Exception\APIException('User ID not set!');
        if (!$this->user->isAllowed("SYS","EVE_DELETE"))
            throw new \Tapi\Exception\APIException('Permission denied!');
        
        $this->urlStart();

        $this->fullUrl .= self::TAPI_NAME . "/" .$this->recId;

        $this->urlEnd();
        
        $this->method = "DELETE";
                
        $this->result = $this->execute();
        return $this;
    }
    
    public function create($eventsArray, $eventTypesArray){
        foreach ($eventsArray as $event) {
            if(!array_key_exists("startTime", $event))
                throw new \Tapi\Exception\APIException('Start time not set!');
            if(!array_key_exists("type", $event))
                throw new \Tapi\Exception\APIException('Type not set!');
            if(!array_key_exists($event["type"], $eventTypesArray))
                throw new \Tapi\Exception\APIException('Unrecognized type!');
        }
        
        $this->urlStart();

        $this->fullUrl .= "events";
        
        $this->method = "POST";
        
        $this->setPostData($eventsArray);
        
        $this->result = $this->execute();

        return $this;
    }
    
    protected function postProcess() {
        if (($data = $this->getData()) == null)
            return;
        
        $data->webName = \Nette\Utils\Strings::webalize($data->id . "-" . $data->caption);

        $this->timeLoad($data->closeTime);
        $this->timeLoad($data->startTime);
        $this->timeLoad($data->endTime);
        $data->resultsClosed = false;
        $myAttendance = new \stdClass();
        $myAttendance->preStatus = "UNKNOWN";
        $myAttendance->postStatus = "UNKNOWN";
        $myAttendance->preDescription = "";
        $myAttendance->postDescription = "";
        if(!property_exists($data, "place")) $data->place = ""; //set default value
        if(!property_exists($data, "link")) $data->link= ""; //set default value
        if (property_exists($data, "attendance"))
            foreach ($data->attendance as $att) {
                if(!property_exists($att, "preStatus")) $att->preStatus = "UNKNOWN"; //set default value
                if(!property_exists($att, "preDescription")) $att->preDescription = ""; //set default value
                if(!property_exists($att, "postStatus")){
                    $att->postStatus = "UNKNOWN"; //set default value
                } else {
                    $data->resultsClosed = true;
                }
                if(!property_exists($att, "postDescription")) $att->postDescription = ""; //set default value
                if (property_exists($att, "preDatMod"))
                    $this->timeLoad($att->preDatMod);
                if (property_exists($att, "postDatMod"))
                    $this->timeLoad($att->postDatMod);
                if($att->userId == $this->user->getId()){
                    $myAttendance = $att;
                }
            }
        $data->myAttendance = $myAttendance;
    }
    
}