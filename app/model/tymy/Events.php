<?php

namespace Tymy;

use Nette;

/**
 * Description of Tymy
 *
 * @author matej
 */
final class Events extends Tymy{
    
    const TAPI_NAME = "events";
    const TSID_REQUIRED = TRUE;
    
    private $from;
    private $order;
    private $to;
    private $withMyAttendance = FALSE;
    public $eventsJSObject;
    public $eventsMonthly;
    public $eventsFrom;
    public $eventsTo;
       
    public function getFrom() {
        return $this->from;
    }

    public function getOrder() {
        return $this->order;
    }

    public function getTo() {
        return $this->to;
    }

    public function getWithMyAttendance() {
        return $this->withMyAttendance;
    }

    public function setFrom($from) {
        $this->from = $from;
        return $this;
    }

    public function setOrder($order) {
        $this->order = $order;
        return $this;
    }

    public function setTo($to) {
        $this->to = $to;
        return $this;
    }

    public function setWithMyAttendance($withMyAttendance) {
        $this->withMyAttendance = $withMyAttendance;
        return $this;
    }
    
    public function reset() {
        $this->setFrom(NULL);
        $this->setTo(NULL);
        $this->setOrder(NULL);
        $this->setWithMyAttendance(FALSE);
        return parent::reset();
    }

        public function select() {
        $url = self::TAPI_NAME;
        
        if($this->withMyAttendance){
            $url .= "/withMyAttendance";
        }
        $filter = [];
        
        if($this->from)
            $filter[] = "startTime>" . $this->from;
        if($this->to)
            $filter[] = "startTime<" . $this->to;
            
        if(count($filter)){
            $this->setUriParam("filter", join("~", $filter));
        }
        
        if($this->order){
            $this->setUriParam("order", $this->order);
        }
        
        $this->fullUrl .= $url;
        return $this;
    }
    
    protected function postProcess(){
        $data = $this->getData();
        
        $this->getResult()->menuWarningCount = 0;
        
        foreach ($data as $event) {
            $event->warning = false;
            if(property_exists($event, "myAttendance") && property_exists($event->myAttendance, "preStatus")){
                $eventClassMap = [
                    "YES" => "success",
                    "LAT" => "warning",
                    "NO" => "danger",
                    "DKY" => "danger",
                    "UNKNOWN" => "secondary",
                ];
                $event->preClass = $eventClassMap[$event->myAttendance->preStatus];
            } else {
                $event->warning = true;
                $this->getResult()->menuWarningCount++;
            }
            
            $this->timeLoad($event->closeTime);
            $this->timeLoad($event->startTime);
            $this->timeLoad($event->endTime);
            $event->webName = \Nette\Utils\Strings::webalize($event->id . "-" . $event->caption);
            if($this->withMyAttendance){
                if(!property_exists($event, "myAttendance")) $event->myAttendance = new \stdClass ();
                if(!property_exists($event->myAttendance, "preStatus")) $event->myAttendance->preStatus = "UNKNOWN"; //set default value
                if(!property_exists($event->myAttendance, "preDescription")) $event->myAttendance->preDescription = ""; //set default value
                if(!property_exists($event->myAttendance, "postStatus")) $event->myAttendance->postStatus = "UNKNOWN"; //set default value
                if(!property_exists($event->myAttendance, "postDescription")) $event->myAttendance->postDescription = ""; //set default value
                if (property_exists($event->myAttendance, "preDatMod")) $this->timeLoad($event->myAttendance->preDatMod);
                if (property_exists($event->myAttendance, "postDatMod")) $this->timeLoad($event->myAttendance->postDatMod);
            } 
        }
    }
    
    public function loadYearEvents($date = NULL, $direction = NULL){
        $this->eventsFrom = date("Ym", strtotime("-6 months")) . "01";
        $this->eventsTo = date("Ym", strtotime("+6 months")) . "01";

        if ($direction == 1) {
            $this->eventsTo = date("Ym", strtotime("$date-01 +6 months")) . "01";
        } elseif ($direction == -1) {
            $this->eventsFrom = date("Ym", strtotime("$date-01 -6 months")) . "01";
        }
        $this->setWithMyAttendance(TRUE)
                ->setFrom($this->eventsFrom)
                ->setTo($this->eventsTo)
                ->setOrder("startTime");
        $this->urlStart();
        $this->select();
        $this->urlEnd();
        try {
            $this->result = $this->execute();
        } catch (\Tymy\Exception\APIAuthenticationException $exc) {
            $this->user->logout(true);
            $this->presenter->flashMessage('You have been signed out due to inactivity. Please sign in again.');
            $this->presenter->redirect('Sign:in', ['backlink' => $this->presenter->storeRequest()]);
        }
        $this->postProcess();
        $data = $this->getData();
        
        $this->eventsJSObject = [];
        $this->eventsMonthly = [];
        
        foreach ($data as $ev) {
            $eventColor = $this->calendarItemColor($ev);
            $eventProps = [
                "id" => $ev->id,
                "title" => $ev->caption,
                "start" => $ev->startTime,
                "end" => $ev->endTime,
                "webName" => $ev->webName
            ];
            $this->eventsJSObject[] = (object)array_merge($eventProps, $eventColor);
            $month = date("Y-m", strtotime($ev->startTime));
            $this->eventsMonthly[$month][] = $ev;
        }
        return $this;
    }
    
    private function calendarItemColor($event) {
        $colorList = $this->supplier->getEventColors();
        $eventColor = [];
        $invertColors = !property_exists($event, 'myAttendance') || !property_exists($event->myAttendance, 'preStatus');
        $eventColor["borderColor"] = $colorList[$event->type];
        $eventColor["backgroundColor"] = $invertColors ? 'white' : $colorList[$event->type];
        $eventColor["textColor"] = $invertColors ? $colorList[$event->type] : '';
        return $eventColor;
    }

}
