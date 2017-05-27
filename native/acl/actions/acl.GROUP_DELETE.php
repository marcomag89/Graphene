<?php
namespace acl;

use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;
use acl\Group;

class GroupDelete extends Action {
    public function run() {
        $groupName = $this->request->getPar('group');
        $group =  new Group();
        $group -> setName($groupName);
        $group -> delete();
        $this  -> sendMessage('group '.$group->getName().' successfully deleted');
    }
}