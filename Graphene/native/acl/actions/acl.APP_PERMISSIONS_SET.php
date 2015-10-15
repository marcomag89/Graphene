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
        $pRes = json_decode($res->getBody(),true);
        if($res->getStatusCode() !==200) throw new GraphException('App info error: '.$pRes['error']['message'],$pRes['error']['code'],400);
        $app = $pRes['App'];
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
            $frwRes=$this->forward('/acl/app/permission',json_encode($req),'DELETE');
            if($frwRes->getStatusCode() !== 200){
                $errs[]=json_decode($frwRes->getBody(),true);
            }
        }

        foreach($doAdd as $prm){
            $req=["AppPermission"=>["apiKey"=>$apiKey,"action"=>$prm]];
            $frwRes = $this->forward('/acl/app/permission',json_encode($req),'POST');
            if($frwRes->getStatusCode() !== 200){
                $errs[]=json_decode($frwRes->getBody(),true);
            }
        }

        $app = json_decode($this->forward('/acl/app/withPermission/'.$apiKey)->getBody(),true);
        $app['errors']=$errs;
        $this->response->setBody(json_encode($app,JSON_PRETTY_PRINT));
    }
}