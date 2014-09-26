<?php
use Graphene\controllers\Action;
use Graphene\Graphene;

class GetModules extends Action
{
	public function run ()
	{
		$this->response->setBody(
			json_encode(
				array(
					'installed-modules' => Graphene::getInstance()->getInstalledModulesInfos()
				), JSON_PRETTY_PRINT));
	}
}