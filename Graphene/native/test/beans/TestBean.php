<?php
use Graphene\models\Bean;
use Graphene\controllers\bean\BeanController;
class TestBean extends Bean{
	public function getStructs(){
		$lazy= array(
			/* 	LABEL		TYPE and CHECKS			DEFAULT VALUE or OBJECT STRUCT*/
				'integer'	.self::INTEGER		=>	null,
				'string'	.self::STRING		=>	null,
				'date'		.self::DATE			=>	null,
				'datetime'	.self::DATETIME		=>	null,
				'boolean'	.self::BOOLEAN		=>	null,
				'double'	.self::DOUBLE		=>	null,
				'enumABC'	.self::ENUM.'a,b,c'	=>	null	
		);
		
		return array(BeanController::LAZY_STRUCT=>$lazy);
	}
}