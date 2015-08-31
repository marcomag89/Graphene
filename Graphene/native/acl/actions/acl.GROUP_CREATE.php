<?php
namespace acl;

use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;
use acl\Group;

class GroupCreate extends Action
{
    public function run(){
       $this->sendModel(Group::getByRequest()->create());
    }
}