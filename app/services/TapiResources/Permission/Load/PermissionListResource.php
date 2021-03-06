<?php

namespace Tapi;

/**
 * Description of PermissionListResource
 *
 * @author kminekmatej, 15.1.2019
 */
class PermissionListResource extends PermissionResource {

    const TYPE_USR = "USR";
    const TYPE_SYS = "SYS";

    public function init() {
        parent::globalInit();
        $this->setCachingTimeout(TapiObject::CACHE_TIMEOUT_LARGE);
        return $this;
    }

    protected function preProcess() {
        $this->setUrl("permissions");
    }

    protected function postProcess() {
        $this->options->usrPermissions = [];
        $this->options->usrPermissionsAsArray = [];
        $this->options->sysPermissions = [];

        if ($this->data == null)
            return null;

        foreach ($this->data as $permission) {
            parent::postProcessPermission($permission);
            if($permission->type == self::TYPE_USR){
                $this->options->usrPermissions[] = $permission;
                $this->options->usrPermissionsAsArray[$permission->name] = $permission->caption;
            } 
            if($permission->type == self::TYPE_SYS) $this->options->sysPermissions[] = $permission;
        }
        
    }

    public function getUsrPermissions() {
        return $this->options->usrPermissions;
    }
    
    public function getUsrPermissionsAsArray() {
        return $this->options->usrPermissionsAsArray;
    }

    public function getSysPermissions() {
        return $this->options->sysPermissions;
    }

    public function getPermissionByWebname($permissionName){
        $allPermissions = $this->getData();
        foreach ($allPermissions as $p) {
            if($p->webName == $permissionName) return $p;
        }
        return NULL;
    }
}
