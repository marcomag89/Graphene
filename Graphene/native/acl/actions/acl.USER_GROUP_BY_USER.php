<?php
namespace acl;

use Graphene\controllers\Action;
use users\User;

class UserGroupByUser extends Action
{
    public function run(){
        $userId=$this->request->getPar('userId');
        $group = new UserGroup();
        $group->setUserId($userId);
        $userGroups = $group->read(true);
        $ret = array();
        $ret[] = Group::$everyoneGroupName;
        if($userGroups !== null){
            foreach ($userGroups as $userGr){
                $ret[]=$userGr->getGroup();
            }
        }
        $this->response->setBody(json_encode(array('UserGroups'=>$ret)));
    }
}