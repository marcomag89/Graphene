<?php
namespace aclo;
use Graphene\models\Model;

class UserGroups extends Model{
	public function defineStruct(){
		return array(
				'groupId'=>Model::UID,
				'permission'=>Model::STRING,
		);
	}
}