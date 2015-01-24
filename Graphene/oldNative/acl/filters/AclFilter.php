<?php
use Graphene\controllers\Filter;

class AclFilter extends Filter{
	public function run (){
		$this->status = 200;
		$this->message = 'non puoi passare';
	}
}