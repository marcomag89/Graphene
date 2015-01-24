<?php
namespace acl;
use Graphene\models\Bean;

class Group extends Bean{
	public function defineStruct(){
		return array(
			'alias'=>Bean::MATCH."/[A-Z0-9_]/". Bean::NOT_NULL.Bean::MIN_LEN.'3'.Bean::UNIQUE .Bean::MAX_LEN.'20',
			'label'=>Bean::STRING.Bean::MAX_LEN.'150',
			'parent'=>Bean::UID,
		);
	}
}