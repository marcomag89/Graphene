<?php
namespace acl;
use Graphene\controllers\Filter;
use Graphene\Graphene;
use \Log;

class AclCheck extends Filter{
    public function run (){

        $user = $this->request->getContextPar('user');
        $permissions = $this->loadPermissions($user);
        $groups      = $this->loadGroups($user);

        $this->request->setContextPar('acl-permissions',$permissions);
        $this->request->setContextPar('acl-groups',$groups);


        //TODO blocca o meno azione in base ai permessi
    }
    private function loadPermissions($user){

        if($user !==null){
            $res= $this->forward('/acl/permission/byUser/'.$user['id']);
            if($res->getStatusCode() === 200){
                $permissions=json_decode($res->getBody(),true)['PermissionSet'];
                $this->request->setContextPar('acl-permissions',$permissions);
            }else{
                Log::err('error when loading user permissions');
            }
        }else{
            $res = $this->forward('/acl/permission/'.Group::$everyoneGroupName);
            if($res->getStatusCode() === 200){
                $permissions = json_decode($res->getBody(),true)['PermissionSet']['permissions'];
            }else{
                Log::err('error when loading anonymous user permissions');
            }
        }
        return $permissions;
    }

    private function loadGroups($user){
        if($user !== null){
            $res= $this->forward('/acl/userGroup/byUser/'.$user['id']);
            if($res->getStatusCode() === 200){
                $groups=json_decode($res->getBody(),true)['UserGroups'];
                $this->request->setContextPar('acl-groups',$groups);
            }else{
                Log::err('error when loading user groups');
            }
        }else{
            $groups = array(Group::$everyoneGroupName);
        }
        return $groups;
    }
}