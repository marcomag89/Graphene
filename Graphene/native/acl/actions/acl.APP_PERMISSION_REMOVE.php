<?php
namespace acl;

use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;

class AppPermissionRemove extends Action
{
    public function run(){
        $appPrm = json_decode($this->request->getBody(),true)['AppPermission'];
        $apiKey = $appPrm['apiKey'];
        $res=$this->forward('/apps/validate/'.$apiKey);
        if($res->getStatusCode() !== 200 ) throw new GraphException('Application not found',400);
        $appId = json_decode($res->getBody(),true)['App']['id'];
        $permission = new AppPermission();
        $permission->setAppId  ($appId);
        $permission->setAction ($appPrm['action']);
        $rPermission=$permission->read();
        if($rPermission !== null){
            $rPermission->delete();
            $this->sendMessage('permission deleted successfully');
        }
        else throw new GraphException('permission not found');
    }
}