<?php
namespace acl;

use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;

class GroupPermissionSet extends Action
{
    public function run(){
        $permissionProto = json_decode($this->request->getBody(),true)['Permission'];
        $group = $permissionProto['group'];
        $permissions = $permissionProto['permissions'];

        $res = $this->forward('/acl/permission/'.$group);
        if($res->getStatusCode() !==200) throw new GraphException('Group not found');
        $app = json_decode($res->getBody(),true)['PermissionSet'];
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

        $errs=[];
        foreach($doRemove as $prm){
            $req = ["Permission"=>["group"=>$group,"action"=>$prm]];
            $frwRes=$this->forward('/acl/permission',json_encode($req),'DELETE');
            if($frwRes->getStatusCode() !== 200){
                $errs[]=json_decode($frwRes->getBody(),true);
            }
        }

        foreach($doAdd as $prm){
            $req=["Permission"=>["group"=>$group,"action"=>$prm]];
            $frwRes=$this->forward('/acl/permission',json_encode($req),'POST');
            if($frwRes->getStatusCode() !== 200){
                $errs[]=json_decode($frwRes->getBody(),true);
            }
        }
        $res = json_decode($this->forward('/acl/permission/'.$group)->getBody(),true);
        $res['errors']=$errs;
        $this->response->setBody(json_encode($res,JSON_PRETTY_PRINT));
    }
}