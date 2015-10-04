<?php
namespace acl;

use Graphene\controllers\Action;

class GroupPermissionAdd extends Action
{
    public function run(){
        $permission = Permission::getByRequest();
        $this->sendModel($permission->create());
    }
}