<?php
namespace acl;

use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;

class GroupPermissionSet extends Action
{
    public function run(){
        $permissionProto = json_decode($this->request->getBody(),true)['Permission'];
        if (!array_key_exists('group', $permissionProto) || !array_key_exists('permissions', $permissionProto))
            throw new GraphException('Invalid permission set request', 400);

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
            try {
                $this->forward('/acl/permission', $req, 'DELETE');
            } catch (\Exception $e) {
                $errs[] = $e->getMessage();
            }
        }

        foreach($doAdd as $prm){
            $req=["Permission"=>["group"=>$group,"action"=>$prm]];
            try {
                $this->forward('/acl/permission', $req, 'POST');
            } catch (\Exception $e1) {
                $errs[] = $e1->getMessage();
            }
        }
        $res = json_decode($this->forward('/acl/permission/'.$group)->getBody(),true);
        $res['errors']=$errs;
        $this->response->setBody(json_encode($res,JSON_PRETTY_PRINT));
    }
}