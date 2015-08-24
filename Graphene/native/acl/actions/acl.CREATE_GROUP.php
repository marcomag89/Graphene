<?php
namespace acl;

use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;
use acl\Group;

class CreateGroup extends Action
{
    public function run(){
        $group = Group::getByRequest();
        $group->standardize();
        if($group->getName() === Group::$superUserGroupName)
            throw new GraphException('cannot use system group name: '.Group::$superUserGroupName.' for group',400);
        if($group->getName() ===Group::$everyoneGroupName)
            throw new GraphException('cannot create system group: '.Group::$everyoneGroupName.' for group name',400);

        if($group->getParent() ===Group::$superUserGroupName)
            throw new GraphException('cannot use system group: '.Group::$superUserGroupName.' as parent',400);

        if($group->getParent() !== Group::$everyoneGroupName){
            $fGroup =new Group();
            $fGroup->setName($group->getParent());
            if($fGroup->read()===null) throw new GraphException('parent group: '.$group->getParent().' does not exists',400);
        }
        $this->sendModel($group->create());
    }
}