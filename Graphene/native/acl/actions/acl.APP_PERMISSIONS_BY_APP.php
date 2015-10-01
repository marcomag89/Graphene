<?php
namespace acl;

use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;

class AppPermissionsByApp extends Action
{
    public function run(){
        $appId = $this->request->getPar('appId');
        $appPerm=new AppPermission();
        $appPerm->setAppId($appId);
        $rdd=$appPerm->read(true);
        $ret=[];
        foreach($rdd as $appPermission){
            $ret[] = $appPermission->getAction();
        }
        $this->response->setBody(json_encode(["AppPermissions"=>$ret]));
    }
}