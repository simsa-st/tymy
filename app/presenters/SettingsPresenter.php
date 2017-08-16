<?php

namespace App\Presenters;

class SettingsPresenter extends SecuredPresenter {
        
    /** @var \Tymy\Event @inject */
    public $event;
        
    /** @var \Tymy\EventTypes @inject */
    public $eventTypes;
        
    protected function startup() {
        parent::startup();
        $this->setLevelCaptions(["1" => ["caption" => "Nastavení", "link" => $this->link("Settings:")]]);
    }

    
    public function actionDefault() {
        //TODO
    }

    public function actionDiscussions($discussion = NULL) {
        $this->setLevelCaptions(["2" => ["caption" => "Diskuze", "link" => $this->link("Settings:discussions")]]);
        if(!is_null($discussion)){
            $this->setView("discussion");
        } else {
            //TODO render list
        }
    }

    public function actionEvents($event = NULL) {
        $this->setLevelCaptions(["2" => ["caption" => "Události", "link" => $this->link("Settings:events")]]);
        if(!is_null($event)){
            $this->setView("event");
        } else {
            //TODO render list
        }
    }

    public function actionPolls($poll = NULL) {
        $this->setLevelCaptions(["2" => ["caption" => "Ankety", "link" => $this->link("Settings:polls")]]);
        if(!is_null($poll)){
            $this->setView("poll");
        } else {
            //TODO render list
        }
    }

    public function actionReports() {
        //TODO
        $this->setLevelCaptions(["2" => ["caption" => "Reporty", "link" => $this->link("Settings:reports")]]);
    }

    public function actionPermissions() {
        //TODO
        $this->setLevelCaptions(["2" => ["caption" => "Oprávnění", "link" => $this->link("Settings:permissions")]]);
    }

    public function actionApp() {
        //TODO
        $this->setLevelCaptions(["2" => ["caption" => "Aplikace", "link" => $this->link("Settings:app")]]);
    }
    
    
    public function renderDiscussion($discussion) {
        //RENDERING DISCUSSION DETAIL
        $discussionId = $this->parseIdFromWebname($discussion);
        $discussionObj = $this->event->reset()->recId($discussionId)->getData();
        $this->setLevelCaptions(["3" => ["caption" => $discussionObj->caption, "link" => $this->link("Settings:discussions", $discussionObj->webName)]]);
        $this->template->discussion = $discussionObj;
    }
    
    public function renderEvent($event) {
        //RENDERING EVENT DETAIL
        $eventId = $this->parseIdFromWebname($event);
        $eventObj = $this->event->reset()->recId($eventId)->getData();
        $this->setLevelCaptions(["3" => ["caption" => $eventObj->caption, "link" => $this->link("Settings:events", $eventObj->webName)]]);
        $this->template->event = $eventObj;
        $eventProps = [];
        $eventProps[] = (object)["name" => "caption", "label" => "Titulek", "type" => "text", "value" => $eventObj->caption];
        $eventProps[] = (object)["name" => "type", "label" => "Typ", "type" => "select", "values"=> $this->eventTypes->getData(), "value" => $eventObj->type];
        $eventProps[] = (object)["name" => "description", "label" => "Popis", "type" => "textarea", "value" => $eventObj->description];
        $eventProps[] = (object)["name" => "startTime", "label" => "Začátek", "type" => "datetime-local", "value" => strftime('%Y-%m-%dT%H:%M:%S', strtotime($eventObj->startTime)) ];
        $eventProps[] = (object)["name" => "endTime", "label" => "Konec", "type" => "datetime-local", "value" => strftime('%Y-%m-%dT%H:%M:%S', strtotime($eventObj->endTime))];
        $eventProps[] = (object)["name" => "closeTime", "label" => "Uzávěrka", "type" => "datetime-local", "value" => strftime('%Y-%m-%dT%H:%M:%S', strtotime($eventObj->closeTime))];
        $eventProps[] = (object)["name" => "place", "label" => "Místo", "type" => "text", "value" => $eventObj->place];
        $eventProps[] = (object)["name" => "link", "label" => "Odkaz", "type" => "text", "value" => $eventObj->link];
        
        $this->template->props = $eventProps;
    }
    
    public function renderPoll($poll) {
        //RENDERING POLL DETAIL
        $pollId = $this->parseIdFromWebname($poll);
        $pollObj = $this->event->reset()->recId($pollId)->getData();
        $this->setLevelCaptions(["3" => ["caption" => $pollObj->caption, "link" => $this->link("Settings:polls", $pollObj->webName)]]);
        $this->template->poll = $pollObj;
    }
    
    public function handleEventEdit($eventId){
        $this->flashMessage("Event edited");
    }
    
    public function handleEventDelete($eventId){
        $this->flashMessage("Event deleted");
    }
    
}
