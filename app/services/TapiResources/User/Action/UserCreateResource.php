<?php

namespace Tapi;

/**
 * Project: tymy_v2
 * Description of UserCreateResource
 *
 * @author kminekmatej created on 29.12.2017, 19:58:05
 */
class UserCreateResource extends UserResource {
    
    protected function init() {
        $this->setCacheable(FALSE);
        $this->setMethod(RequestMethod::POST);
    }

    protected function preProcess() {
        if ($this->getUser())
            throw new APIException('User not set!');
        
        $this->setUrl("users");
        $this->setRequestData($this->user);

        return $this;
    }
    
    protected function postProcess() {
        parent::postProcessUser($this->data);
    }
    
}
