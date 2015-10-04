<?php
namespace acl;

use Graphene\controllers\Action;

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
        foreach($doRemove as $prm){
            $req = ["Permission"=>["group"=>$group,"action"=>$prm]];
            $this->forward('/acl/permission',json_encode($req),'DELETE');
        }

        foreach($doAdd as $prm){
            $req=["Permission"=>["group"=>$group,"action"=>$prm]];
            $this->forward('/acl/permission',json_encode($req),'POST');
        }

        $res = json_decode($this->forward('/acl/permission/'.$group)->getBody(),true);
        $this->response->setBody(json_encode($res,JSON_PRETTY_PRINT));
    }
}