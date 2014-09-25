<?php
use Graphene\controllers\Action;
use Graphene\Graphene;

class GetStatus extends Action
{

	public function run ()
	{
		$this->status = array();
		$fw = Graphene::getInstance();
		$this->status['framework-infos'] = Graphene::INFO;
		$this->status['framework-version'] = Graphene::VERSION;
		$this->status['app-name'] = $fw->getApplicationName();
		$this->status['installed-modules'] = count(
				$fw->getInstalledModulesInfos());
		if ($fw->getStorage()->checkConnection())
			$this->status['db']['connectionStatus'] = 'ok';
		else
			$this->status['db']['connectionStatus'] = 'connection fails';
		$this->status['db']['driver'] = $fw->getStorage()->getDriverInfos();
		// Sending response
		$this->status['server']['time']=date('Y-m-d H:i:s');
		$this->response->setBody($this->getStatusBody());
	}

	private function getStatusBody ()
	{
		return json_encode(
				array(
					'GrapheneStatus' => $this->status
				), JSON_PRETTY_PRINT);
	}

	private $status;
}