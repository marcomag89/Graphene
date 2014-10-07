<?php
namespace grTests;
use Graphene\models\Bean;
use Graphene\controllers\bean\BeanController;
use Graphene\controllers\bean\Struct;
class TestBean extends Bean{
	public function defineStruct(){	
		return array(
				'integer' 		=> Bean::INTEGER.	Bean::NOT_EMPTY.	Bean::UNIQUE,
				'string' 	 	=> Bean::STRING.	Bean::NOT_EMPTY,
				'string50'		=> Bean::STRING.	Bean::MAX_LEN.'50',
				'boolean'		=> Bean::BOOLEAN,
				'date'	  		=> Bean::DATE.		Bean::NOT_EMPTY,
				'alphanumeric'	=> Bean::MATCH.'/^[A-Za-z0-9]+$/',
				'enumABC'		=> Bean::ENUM.'A,B,C',
				'object'  =>array(
								'stringField'	=> Bean::STRING,
								'intField'		=> Bean::INTEGER,
							)
		);
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
			
		return $lazy;
	}
}