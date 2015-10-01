<?php
namespace acl;

use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;

class PermissionGroupRead extends Action
{
    /**
     * @throws GraphException
     */
    public function run(){
        $groupName = strtoupper($this->request->getPar('group'));
        $tGroupName = $groupName;
        $retPermissions = array();
        do{
            $top = ($tGroupName === Group::$everyoneGroupName);
            if(!$top){
                $tGroup =  new Group();
                $tGroup -> setName($tGroupName);
                $tGroup = $tGroup->read();
                if($tGroup === null){
                    if(strcasecmp($groupName,$tGroupName)===0){ throw new GraphException('group '.$groupName.' not found',400);}
                    else{throw new GraphException('parent group '.$tGroupName.' not found',400);}
                }

            }
            $permissions = new Permission();
            $permissions ->setGroup($tGroupName);
            $oPermissions = $permissions->read(true);
            if($oPermissions !== null){
                foreach($oPermissions as $permission){
                    if(array_search($permission->getAction(),$retPermissions,true)===false){
                        $retPermissions[] = $permission->getAction();
                    }
                }
            }
            if(!$top) $tGroupName = $tGroup->getParent();
        }while(!$top);
        $this->response->setBody(json_encode(array("PermissionSet"=>array("group"=>$groupName,"permissions"=>$retPermissions)),JSON_PRETTY_PRINT));
    }
}