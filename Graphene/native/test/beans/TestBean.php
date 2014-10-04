<?php
namespace grTests;
use Graphene\models\Bean;
use Graphene\controllers\bean\BeanController;
use Graphene\controllers\bean\Struct;
class TestBean extends Bean{
	public function getStructs(){
		$lazy=array();
		//Single fields
		$lazy['integer']			=Bean::INTEGER;
		$lazy['string']				=Bean::STRING;
		$lazy['date']				=Bean::DATE;
		$lazy['boolean']			=Bean::BOOLEAN;
		$lazy['double']				=Bean::DECIMAL;
		$lazy['notEmpty']			=Bean::NOT_EMPTY;
		$lazy['integerNotNull']		=Bean::NOT_NULL.Bean::INTEGER;
		$lazy['enumABC']			=Bean::ENUM.'A,B,C';
		
		//Objects
		$lazy['object']['stringField']=Bean::STRING;
		$lazy['object']['intField']=Bean::INTEGER;
		$lazy['object']['booleanField']=Bean::BOOLEAN;
		
		//TODO implementare questa cosa
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