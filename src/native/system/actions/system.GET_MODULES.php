<?php
namespace system;
use Graphene\controllers\Action;
use Graphene\Graphene;
use \Exception;

class GetModules extends Action {
	public function run() {
        $this->response->setBody ( json_encode (
            array ('InstalledModules' => Graphene::getInstance ()->getInstalledModulesInfos()
            ), JSON_PRETTY_PRINT ) );
	}
}