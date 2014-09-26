<?php
namespace grSystem;

use Graphene\models\Bean;
use Graphene\controllers\bean\BeanController;
class FooBarBean extends Bean{
	public function getStructs(){
		$l=array();
		$l['foo']['barOne']=Bean::NOT_EMPTY;
		$l['foo']['barTwo']=Bean::NOT_EMPTY;
		$l['foo']['barThree']=Bean::NOT_EMPTY;
		$l['foo']['fooTwo']['bar']=Bean::NOT_EMPTY;
		$l['nodeOne']=Bean::NOT_EMPTY;
		return array(BeanController::LAZY_STRUCT=>$l);
	}
}