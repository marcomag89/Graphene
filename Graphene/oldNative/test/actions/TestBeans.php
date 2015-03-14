<?php
namespace grTests;
use Graphene\controllers\Action;
use Graphene\controllers\model\Struct;
use Graphene\models\Model;

class Create extends Action{
	public function run(){
		$test=TestModel::getByRequest();
		$this->sendModel($test->create());
	}	
}
class Read extends Action{
	public function run(){
		$test=new TestModel();
		$this->sendModel($test->read());
	}
}
class Update extends Action{
	public function run(){
		$test=TestModel::getByRequest();
		$this->sendModel($test->update());
	}
}
class Delete extends Action{
	public function run(){
		$test=TestModel::getByRequest();
		if($test->delete()){
			$this->sendMessage('Test deleted');
		}else{
			$this->sendError(400, 'error on delete');				
		}
	}
}