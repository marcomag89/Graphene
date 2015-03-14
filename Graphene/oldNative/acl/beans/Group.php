<?php
namespace acl;
use Graphene\models\Model;

class Group extends Model{
	public function defineStruct(){
		return array(
			'alias'=>Model::MATCH."/[A-Z0-9_]/". Model::NOT_NULL.Model::MIN_LEN.'3'.Model::UNIQUE .Model::MAX_LEN.'20',
			'label'=>Model::STRING.Model::MAX_LEN.'150',
			'parent'=>Model::UID,
		);
	}
}