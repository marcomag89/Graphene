<?php
namespace acl;

use Graphene\controllers\Action;

class AppPermissionRemove extends Action
{
    public function run(){
        $permission = AppPermission::getByRequest();
        $permission->delete();
        $this->sendMessage('app permission '.$permission->getAction().' removed');
    }
}