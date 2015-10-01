<?php
namespace acl;
use Graphene\controllers\Filter;
use \Log;

class AclCheck extends Filter{
    public function run (){
        $actionName      = $this->action->getUniqueActionName();
        $user            = $this->request->getContextPar('user');
        $appPermissions  = $this->loadAppPermissions($this->request->getHeader('apiKey'));
        $permissions     = $this->loadPermissions($user);
        $groups          = $this->loadGroups($user);
        $filterEnabled   = false;

        $res=$this->forward('/acl/userGroup/byGroup/'.Group::$superUserGroupName);
        if($res->getStatusCode() === 200){
            $users  = json_decode($res->getBody(),true)['GroupUsers']['users'];
            $filterEnabled = (count($users)>0);
        }

        if(
            $filterEnabled                                             && //Filtro abilitato
            array_search(Group::$superUserGroupName,$groups) === false && //Utente non super_user
            (
            array_search($actionName,$permissions)           === false    //Permesso utente non trovato
            ||                                                            //         oppure
            array_search($actionName,$appPermissions)        === false    //Permesso applicazione non trovato
            )
        ){
            $this->status=300;
            $this->message='Access denied to action: '.$this->action->getUniqueActionName();
        }

        $this->request->setContextPar('acl-permissions',$permissions);
        $this->request->setContextPar('acl-groups',$groups);

    }

    private function loadAppPermissions($apiKey){
        if($apiKey === null)return [];
        $res = $this->forward('/acl/app/validate/'.$apiKey);
        if($res->getStatusCode() !== 200) return [];
        return json_decode(json_decode($res->getBody(),true)['App']['permissions']);
    }

    private function loadPermissions($user){
        $permissions = array();

        if($user !== null){
            $res= $this->forward('/acl/permission/byUser/'.$user['id']);
            if($res->getStatusCode() === 200){
                $permissions = json_decode($res->getBody(),true)['PermissionSet'];
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
                $groups=array();
            }
        }else{
            $groups = array(Group::$everyoneGroupName);
        }
        return $groups;
    }
}