<?php
namespace grSystem; // namespace uguale al namespace del modulo
use Graphene\models\Bean;
use Graphene\controllers\bean\BeanController;

class LogRecord extends Bean
{

	public function getStructs ()
	{
		$ret = array(
			BeanController::LAZY_STRUCT => array(
				'time' => Bean::LONG_VALUE,
				'action' => Bean::STRING_VALUE . Bean::NOT_EMPTY,
				'text' => Bean::STRING_VALUE . Bean::NOT_EMPTY
			),
			'GET_LOG' => array(
				'action' => Bean::STRING_VALUE . Bean::NOT_EMPTY . Bean::NOT_NULL,
				'text' => Bean::STRING_VALUE . Bean::NOT_EMPTY . Bean::NOT_NULL
			)
		);
		$ret['WRITE_LOG'] = $ret['GET_LOG'];
		return $ret;
	}
}