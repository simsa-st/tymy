<?php

namespace Tapi;

/**
 * Project: tymy_v2
 * Description of UsersLiveResource
 *
 * @author kminekmatej created on 29.12.2017, 19:53:44
 */
class UsersLiveResource extends UserResource {
    
    protected function init() {
        $this->setCacheable(FALSE);
    }

    protected function preProcess() {
        $this->setUrl("live");
        return $this;
    }
    
    protected function postProcess() {
        foreach ($this->data as $user) {
            parent::postProcessSimpleUser($user);
        }
        
    }

}
