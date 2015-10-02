<?php
namespace acl;

use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;

class AppPermissionsSet extends Action
{
    public function run(){
        $appProto = json_decode($this->request->getBody(),true)['App'];
        $apiKey = $appProto['apiKey'];
        $permissions = $appProto['permissions'];
        $res = $this->forward('/acl/app/withPermission/'.$apiKey);
        if($res->getStatusCode() !==200) throw new GraphException('Application not found');
        $app = json_decode($res->getBody(),true)['App'];
        $rPermissions = $app['permissions'];
        $doAdd   =[];
        $doRemove=[];

        //controllo permessi da aggiungere
        foreach($permissions as $permission){
            if(!in_array($permission,$rPermissions)){
                $doAdd[]=$permission;
            }
        }

        //controllo permessi da rimuovere
        foreach($rPermissions as $permission){
            if(!in_array($permission,$permissions)){
                $doRemove[]=$permission;
            }
        }
        foreach($doRemove as $prm){
            $req = ["AppPermission"=>["apiKey"=>$apiKey,"action"=>$prm]];
            $this->forward('/acl/app/permission',json_encode($req),'DELETE');
        }

        foreach($doAdd as $prm){
            $req=["AppPermission"=>["apiKey"=>$apiKey,"action"=>$prm]];
            $res = $this->forward('/acl/app/permission',json_encode($req),'POST');
        }

        $app = json_decode($this->forward('/acl/app/withPermission/'.$apiKey)->getBody(),true);
        $this->response->setBody(json_encode($app,JSON_PRETTY_PRINT));
    }
}