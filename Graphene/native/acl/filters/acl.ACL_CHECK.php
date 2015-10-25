<?php
namespace acl;
use Graphene\controllers\Filter;
use \Log;

class AclCheck extends Filter{
    public function run (){
        $actionName      = $this->action->getUniqueActionName();
        $user            = $this->request->getContextPar('user');
        $apiKey          = $this->request->getHeader('api-key');
        $appInfo         = $this->loadAppInfo($apiKey);
        $permissions     = $this->loadPermissions($user);
        $groups          = $this->loadGroups($user);
        if(array_key_exists('permissions',$appInfo))
            $appPermissions  = $appInfo['permissions'];
        else $appPermissions = [];

        $filterEnabled   = false;
        $res=$this->forward('/acl/userGroup/byGroup/'.Group::$superUserGroupName);
        if($res->getStatusCode() === 200){
            $users  = json_decode($res->getBody(),true)['GroupUsers']['users'];
            $filterEnabled = (count($users)>0);
        }

        if(
            $filterEnabled  && //Filtro abilitato
            (
                !$this->enabledTo($actionName,$appPermissions)|| //Permesso applicazione non trovato
                (
                    array_search(Group::$superUserGroupName,$groups) === false && //Utente non super_user
                    !$this->enabledTo($actionName,$permissions)                   //Permesso utente non trovato
                )
            )
        ){
            $this->status=300;
            $this->message='Access denied to action: '.$this->action->getUniqueActionName();
        }
        $this->request->setContextPar('acl-enabled'    ,$filterEnabled);
        $this->request->setContextPar('acl-app-info'   ,$appInfo);
        $this->request->setContextPar('acl-permissions',$permissions);
        $this->request->setContextPar('acl-groups'     ,$groups);
    }

    private function enabledTo($actionName, $permissionList){
        foreach($this->aclExceptions as $exc){
            if($this->matches($exc,$actionName)){
                return true;
            }
        }
        foreach($permissionList as $prm){
            if($this->matches($prm,$actionName)){
                return true;
            }
        }
        return false;
    }

    private function matches($needle,$action){
        $needle = strtoupper($needle);
        $action = strtoupper($action);
        if(str_ends_with($needle,'.*') && str_starts_with($action, substr($needle,0,strlen($needle-1))))return true;
        else if($needle === $action) return true;
        else return false;
    }

    private function loadAppInfo($apiKey){
        if($apiKey === null)return [];
        $res = $this->forward('/acl/app/withPermission/'.$apiKey);
        if($res->getStatusCode() !== 200) return [];
        return json_decode($res->getBody(),true)['App'];
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
        } else {
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
    private $aclExceptions=[
        'SYSTEM.GET_CLIENT_STATUS',
        'SYSTEM.DOC_ACTION_BY_NAME'
    ];
}