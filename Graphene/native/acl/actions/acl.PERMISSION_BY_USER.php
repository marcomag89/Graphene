<?php
namespace acl;

use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;
use \Log;

class PermissionByUser extends Action
{
    public function run(){
        $userId=$this->request->getPar('userId');
        $res = $this->forward('/acl/userGroup/byUser/'.$userId);
        if($res->getStatusCode()!== 200)throw new GraphException('error when loading user groups');
        $retPermissions = array();
        $groups=json_decode($res->getBody(),true)['UserGroups'];
        foreach($groups as $group){
            $pRes=$this->forward('/acl/permission/'.$group);
            if($pRes->getStatusCode()!== 200) {
                Log::err('error on loading user group: ' . $group);
            }else{
                $permissions = json_decode($pRes->getBody(),true)['PermissionSet']['permissions'];
                foreach($permissions as $perm){
                    if(array_search($perm,$retPermissions,true)===false)
                        $retPermissions[] = $perm;
                }
            }
        }
        $this->response->setBody(json_encode(array('PermissionSet'=>$retPermissions),JSON_PRETTY_PRINT));
    }
}