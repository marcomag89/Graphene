<?php
namespace acl;
use Graphene\controllers\Action;
use acl\Group;

class CreateGroup extends Action{
	public function run (){
		$group = Group::getByRequest();
		$created = $group->create();
		$this->sendBean($created[0]);
	}
}