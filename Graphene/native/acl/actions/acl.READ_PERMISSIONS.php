<?php
namespace acl;

use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;

class ReadPermissions extends Action
{
    /**
     * @throws GraphException
     */
    public function run(){
        $groupName = strtoupper($this->request->getPar('group'));
        $top = false;
        $tGroupName = $groupName;
        $retPermissions=array();
        while(!$top){
            $tGroup =  new Group();
            $tGroup -> setName($tGroupName);
            $tGroup = $tGroup->read();
            if($tGroup->read() === null){
                if(strcasecmp($groupName,$tGroupName)===0){ throw new GraphException('group '.$groupName.' not found',400);}
                else{throw new GraphException('parent group '.$tGroupName.' not found',400);}
            }else{
                $permissions = new Permission();
                $permissions->setGroup($tGroupName);
                $oPermissions= $permissions->read(true);
                foreach($oPermissions as $permission){
                    $retPermissions[] = $permission->getAction();
                }
                $tGroupName = $tGroup->getParent();
                $top = ($tGroup->getParent() === Group::$everyoneGroupName);
            }
        }

        $this->response->setBody(json_encode(array("PermissionSet"=>array("group"=>$groupName,"permissions"=>$retPermissions)),JSON_PRETTY_PRINT));
    }
}