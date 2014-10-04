<?php
namespace grTests;
use Graphene\models\Bean;
use Graphene\controllers\bean\BeanController;
use Graphene\controllers\bean\Struct;
class TestBean extends Bean{
	public function getStructs(){
		$lazy=array();
		//Single fields
		$lazy['integer']			=Bean::INTEGER_VALUE;
		$lazy['string']				=Bean::STRING_VALUE;
		$lazy['date']				=Bean::DATE_VALUE;
		$lazy['boolean']			=Bean::BOOLEAN_VALUE;
		$lazy['double']				=Bean::DOUBLE_VALUE;
		$lazy['enumABC']			=Bean::ENUM_VALUE.'A,B,C';
		
		//Objects
		$lazy['object']['stringField']=Bean::STRING_VALUE;
		$lazy['object']['intField']=Bean::INTEGER_VALUE;
		$lazy['object']['booleanField']=Bean::BOOLEAN_VALUE;
		
		/* 
		 #	Sets prototype of array
		*/
		/*
		 * INTEGER Prototype
		 * Every element of array must be an integer value
		 * */
		//$lazy['array']['#']=Bean::INTEGER_VALUE;
		/*
		 * INTEGER or Double Prototype
		 * Every element of array must be an integer value
		 * */
		//$lazy['array']['#']=array(Bean::INTEGER_VALUE,Bean::DOUBLE_VALUE);
		/*
		 * OBJECT Prototype
		 * Every element of array must be a defined object
		 * */
		//$lazy['array']['#']['ObjectName']['integerField']=Bean::INTEGER_VALUE;
		//$lazy['array']['#']['ObjectName']['stringField']=Bean::STRING_VALUE;
			
		return array(BeanController::LAZY_STRUCT=>$lazy);
	}
}