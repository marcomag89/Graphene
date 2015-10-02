<?php
namespace acl;

use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;

class AppPermissionsByApp extends Action
{
    public function run(){
        $apiKey = $this->request->getPar('apiKey');
        $res = $this->forward('/apps/validate/'.$apiKey);
        if($res->getStatusCode() !==200)throw new GraphException('Application not found');
        $app = json_decode($res->getBody(),true)['App'];
        $appPerm=new AppPermission();
        $appPerm->setAppId($app['id']);
        $rdd=$appPerm->read(true);
        $ret=[];
        foreach($rdd as $appPermission){
            $ret[] = $appPermission->getAction();
        }
        $this->response->setBody(json_encode(["AppPermissions"=>$ret]));
    }
}