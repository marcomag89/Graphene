<?php

namespace apps;
use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;

class Validate extends Action{
    function run(){
        $apikey = $this->request->getPar('apikey');
        $app=new App();
        $app->setApiKey($apikey);
        $appRdd=$app->read();
        if($appRdd === null)throw new GraphException('Application unauthorized',401,401);
        $this->sendModel($appRdd);
    }
}