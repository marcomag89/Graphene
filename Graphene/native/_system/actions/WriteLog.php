<?php
use Graphene\controllers\Action;
use grSystem\LogRecord;

class WriteLog extends Action
{

	public function run ()
	{
		$entry = new LogRecord(null, $this->request);
		$entry->setTime(round(microtime(true) * 1000));
		if ($entry->store()) {
			$this->sendBean($entry);
		} else
			$this->sendError(500, 'can\'t store your entry');
	}
}