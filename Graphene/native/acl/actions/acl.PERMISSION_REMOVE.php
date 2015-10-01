<?php
namespace acl;

use Graphene\controllers\Action;

class PermissionRemove extends Action
{
    public function run(){
        $permission = Permission::getByRequest();
        $permission->delete();
        $this->sendMessage('permission '.$permission->getAction().' removed from '.$permission->getGroup());
    }
}