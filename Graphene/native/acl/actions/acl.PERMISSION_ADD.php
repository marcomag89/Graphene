<?php
namespace acl;

use Graphene\controllers\Action;

class PermissionAdd extends Action
{
    public function run(){
        $permission = Permission::getByRequest();
        $this->sendModel($permission->create());
    }
}