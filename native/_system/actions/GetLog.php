<?php
use Graphene\controllers\Action;
use system\LogRecord;
use Graphene\controllers\ExceptionsCodes;

class GetLog extends Action
{

	public function run ()
	{
		$loads = new LogRecord();
		$this->sendBean($loads->read());
	}

	public function getErrorCode ($e)
	{
		return $this->getClientSideErrorCode($e);
	}
}