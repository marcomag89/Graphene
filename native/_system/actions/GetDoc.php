<?php
use Graphene\controllers\Action;
use Graphene\_system\lib\TemplateManager;

class GetDoc extends Action
{

	public function __construct ()
	{
	}

	public function run ()
	{
		//$this->tm = new TemplateManager();
		//$this->response->setHeader('content-type', 'text/HTML');
		echo 'documentazione per: '.$this->request->getPar('action');
		
	}

	private function checkAction ()
	{
		if (isset($this->request->getPars()['action']) &&
				 $this->request->getPars()['action'] != '') {
			$action = $this->request->getPars()['action'];
			if ($this->getActionModule($action)) {} else
				$this->sendError(404, 
						'Action ' . $action . ' not found on this server');
		} else
			$this->sendError(400, 'unavailable action for documentation, call this with: [serveraddr]/system/doc/AN_ACTION');
	}

	private function getActionModule ($action)
	{
		return false;
	}

	/*public function sendError ($err_code, $err_message)
	{
		$this->response->setStatusCode($err_code);
		$this->response->setBody(
				$this->tm->getErrorTemplate($err_code, $err_message));
	}
	*/
	private $tm;
}