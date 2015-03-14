<?php
namespace acl;
use Graphene\models\Model;

class Permission extends Model{
	public function defineStruct(){
		return array(
				'groupId'=>Model::UID,
				'permission'=>Model::STRING,
		);
	}
}