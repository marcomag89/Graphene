<?php
namespace grTests;
use Graphene\models\Model;
use Graphene\controllers\model\ModelController;
use Graphene\controllers\model\Struct;
class TestModel extends Model{
	public function defineStruct(){	
		return array(
				'integer' 		=> Model::INTEGER.	Model::NOT_EMPTY.	Model::UNIQUE,
				'string' 	 	=> Model::STRING.	Model::NOT_EMPTY,
				'string50'		=> Model::STRING.	Model::MAX_LEN.'50',
				'boolean'		=> Model::BOOLEAN,
				'date'	  		=> Model::DATE.		Model::NOT_EMPTY,
				'alphanumeric'	=> Model::MATCH.'/^[A-Za-z0-9]+$/',
				'enumABC'		=> Model::ENUM.'A,B,C',
				'object'  =>array(
								'stringField'	=> Model::STRING,
								'intField'		=> Model::INTEGER,
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
		//$lazy['array']['#']=Model::INTEGER_VALUE;
		/*
		 * INTEGER or Double Prototype
		 * Every element of array must be an integer value
		 * */
		//$lazy['array']['#']=array(Model::INTEGER_VALUE,Model::DOUBLE_VALUE);
		/*
		 * OBJECT Prototype
		 * Every element of array must be a defined object
		 * */
		//$lazy['array']['#']['ObjectName']['integerField']=Model::INTEGER_VALUE;
		//$lazy['array']['#']['ObjectName']['stringField']=Model::STRING_VALUE;
			
		return $lazy;
	}
}