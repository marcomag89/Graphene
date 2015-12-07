<?php
namespace system;
use Graphene\controllers\Action;
use Graphene\Graphene;

class GetStatus extends Action
{

	public function run()
	{
		$this->status = array();
		$fw   = Graphene::getInstance();
		$mods = $fw->getInstalledModulesInfos();
		$this->status['framework-infos'] = Graphene::INFO;
		$this->status['framework-version'] = Graphene::VERSION;
		$this->status['php-version'] = 'PHP v.'.phpversion();
		$this->status['app-name'] = $fw->getApplicationName();

		if ($fw->getStorage()->checkConnection())
			$this->status['db']['connectionStatus'] = 'ok';
		else
			$this->status['db']['connectionStatus'] = 'connection fails';

		$this->status['db']['driver'] = $fw->getStorage()->getDriverInfos();

		// Sending response
		$this->status['server']['time']=date('Y-m-d H:i:s');
		$this->status['server']['ip-address']=$_SERVER['SERVER_ADDR'];
		$this->status['server']['software']=$_SERVER['SERVER_SOFTWARE'];

		$this->status['installed-modules'] = array();
		foreach($mods as $mod){
			$this->status['installed-modules'][] = str_pad($mod['name'],20).' ['.count($mod['actions']).']';
		}

        $this->response->setBody($this->getStatusBody());

	}//end run()

	private function getStatusBody() {
		return json_encode(array('GrapheneStatus' => $this->status), JSON_PRETTY_PRINT);
	}//end getStatusBody()

	private $status;

}//end class