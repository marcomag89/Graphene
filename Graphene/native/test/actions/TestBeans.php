<?php
namespace grTests;
use Graphene\controllers\Action;
use Graphene\controllers\bean\Struct;
use Graphene\models\Bean;

class TestBeans extends Action{
	public function run(){
		$test=TestBean::getByRequest();
		$this->sendBean($test);
		//$test=new TestBean();
		//echo json_encode($test->getStruct(),JSON_PRETTY_PRINT);
		//echo json_encode(json_decode($this->request->getBody(),true));
	}	
}