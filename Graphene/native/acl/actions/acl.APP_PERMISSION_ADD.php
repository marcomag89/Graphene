<?php
namespace acl;

use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;

class AppPermissionAdd extends Action
{
    public function run(){
        $permission = AppPermission::getByRequest();
        if($permission->read() === null) $this -> sendModel($permission->create());
        else throw new GraphException('permission already assigned');
    }
}