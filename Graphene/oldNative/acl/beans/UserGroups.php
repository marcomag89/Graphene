<?php
namespace acl;
use Graphene\models\Bean;

class UserGroups extends Bean{
	public function defineStruct(){
		return array(
				'groupId'=>Bean::UID,
				'permission'=>Bean::STRING,
		);
	}
}