<?php
namespace grTests;
use Graphene\controllers\Action;
use Graphene\controllers\bean\Struct;
use Graphene\models\Bean;

class Create extends Action{
	public function run(){
		$test=TestBean::getByRequest(true);
		$this->sendBean($test->create());
	}	
}
class Read extends Action{
	public function run(){
		$test=new TestBean();
		$this->sendBean($test->read());
	}
}
class Update extends Action{
	public function run(){
		$test=TestBean::getByRequest();
		$this->sendBean($test->update());
	}
}
class Delete extends Action{
	public function run(){
		$test=TestBean::getByRequest();
		if($test->delete()){
			$this->sendMessage('Test deleted');
		}else{
			$this->sendError(400, 'error on delete');				
		}
	}
}