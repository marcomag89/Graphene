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
        $res=$this->forward('/acl/app/validate/'.$apiKey);
        if($res->getStatusCode() !==200) throw new GraphException('Application not found');
        $app =json_decode($res->getBody(),true)['App'];
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
            $oPrm = new AppPermission();
            $oPrm->setAppId($app['id']);
            $oPrm->setAction($prm);
            $oPrm=$oPrm->read();
            if($oPrm !==null){
                $this->forward('/acl/permissions',$oPrm->serialize(),'DELETE');
            }
        }

        foreach($doAdd as $prm){
            $oPrm = new AppPermission();
            $oPrm->setAppId($app['id']);
            $oPrm->setAction($prm);
            $res = $this->forward('/acl/app/permission',$oPrm->serialize(),'POST');
        }

        $this->response->setBody($this->forward('/acl/app/validate/'.$apiKey)->getBody());
       // print_r(["doAdd"=>$doAdd,"doRemove"=>$doRemove]);
    }
}