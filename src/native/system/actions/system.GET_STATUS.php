<?php
namespace system;

use Graphene\controllers\Action;
use Graphene\Graphene;

class GetStatus extends Action {

    private $status;//end run()

    public function run() {
        $this->status = [];
        $fw = Graphene::getInstance();
        $mods = $fw->getInstalledModulesInfos();
        $this->status['framework'] = [
            'info'    => Graphene::INFO,
            'version' => Graphene::VERSION,
        ];
        $this->status['php'] = 'PHP v.' . phpversion();
        $this->status['appName'] = $fw->getApplicationName();

        if ($fw->getStorage()->checkConnection()) {
            $this->status['db']['connectionStatus'] = 'ok';
        } else {
            $this->status['db']['connectionStatus'] = 'connection fails';
        }

        $this->status['db']['driver'] = $fw->getStorage()->getDriverInfos();

        // Sending response
        $this->status['server']['time'] = date('Y-m-d H:i:s');
        $this->status['server']['ip'] = array_key_exists('SERVER_ADDR', $_SERVER) ? $_SERVER['SERVER_ADDR'] : 'ND';
        $this->status['server']['software'] = $_SERVER['SERVER_SOFTWARE'];

        $this->status['installedModules'] = [];
        foreach ($mods as $mod) {

            $this->status['modules'][] = [
                'name'    => $mod['name'],
                'actions' => count($mod['actions'])
            ];
        }
        $this->send(['GrapheneStatus' => $this->status]);

    }//end

}//end class