<?php
namespace acl;

use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;

class AddPermission extends Action
{
    public function run(){
        $permission = Permission::getByRequest();
        $permission->serialize();
        $group = new Group();
        $group -> setName($permission->getGroup());
        $oGroup=$group->read();
        if($oGroup === null)
            throw new GraphException('Group '.$permission->getGroup(). ' does not exists',400);
        if($permission->read()!== null)
            throw new GraphException('Group '.$permission->getAction(). ' already assigned to '.$permission->getGroup(),400);
        $this->sendModel($permission->create());
    }
}