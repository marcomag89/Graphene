<?php
namespace acl;

use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;

class UserGroupSet extends Action{
    public function run(){
        $userGroup =  UserGroup::getByRequest();
        $res       = $this->forward('/users/user/'.$userGroup->getUserId());
        if($res->getStatusCode() !== 200) throw new GraphException('user id is not valid',400);
        /*
        $userGroup -> standardize();
        $res = $this->forward('/users/user/'.$userGroup->getUserId());
        $group = new Group();
        $group->setName($userGroup->getGroup());
        if($res->getStatusCode() !== 200) throw new GraphException('user id is not valid',400);
        if(
            $userGroup->getGroup() !== Group::$superUserGroupName &&
            $userGroup->getGroup() !== Group::$everyoneGroupName  &&
            $group->read() === null
        ) throw new GraphException('group '.$userGroup->getGroup().' does not exists',400);
        elseif($userGroup->getGroup()===Group::$superUserGroupName){
            $tUserGroup =new UserGroup();
            $tUserGroup->setGroup(Group::$superUserGroupName);
            if($tUserGroup->read()!==null)throw new GraphException('Super user group was already assigned',400);
        }

        if($userGroup->read() !== null)   throw new GraphException('user already associated in '.$userGroup->getGroup(),400);
        */

        $this->sendModel($userGroup->create());
    }
}