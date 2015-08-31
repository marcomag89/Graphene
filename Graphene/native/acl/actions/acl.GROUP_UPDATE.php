<?php
namespace acl;

use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;
use acl\Group;

class GroupUpdate extends Action
{
    public function run(){
        $groupName = Group::standardizeGroupName($this->request->getPar('group'));
        $group =  new Group();
        $group -> setName($groupName);
        $group =  $group->read();
        if($group === null) throw new GraphException('Group '.$groupName.' not found');

        $nGroup =  Group::getByRequest();
        $nGroup -> setId($group->getId());
        $nGroup -> setVersion($group->getVersion());

        $this   -> sendModel($nGroup->update());
    }
}