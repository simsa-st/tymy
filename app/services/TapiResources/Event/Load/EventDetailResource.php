<?php

namespace Tapi;
use Tymy\Exception\APIException;

/**
 * Project: tymy_v2
 * Description of EventDetailResource
 *
 * @author kminekmatej created on 22.12.2017, 21:08:02
 */
class EventDetailResource extends EventResource {
    
    public function init() {
        //everything inites correctly
    }

    protected function preProcess() {
        if($this->getId() == null)
            throw new APIException('Event id not set!');
        $this->setUrl("event/" . $this->getId());
    }
    
    protected function postProcess() {
        parent::postProcessEvent($this->data);
    }

}