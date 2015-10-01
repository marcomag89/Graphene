<?php

namespace acl;
use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;

class AppValidate extends Action{
    function run(){
        $apikey = $this->request->getPar('apikey');
        $app=new App();
        $app->setApiKey($apikey);
        $AppRdd=$app->read();
        if($AppRdd === null)throw new GraphException('Application unauthorized',401,401);
        $appPerm=new AppPermission();
        $appPerm->setAppId($AppRdd->getAppId);
        $rdd = $appPerm->read(true);
        $prm=[];
        foreach($rdd as $appPermission){
            $prm[] = $appPermission->getAction();
        }
        $ret['App'] = $AppRdd->getContent();
        $ret['App']['permissions']=$prm;
        $this->response->setBody(json_encode($ret));
    }
}