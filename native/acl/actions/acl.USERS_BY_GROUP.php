<?php
namespace acl;

use Graphene\controllers\Action;
use \Log;

class UsersByGroup extends Action
{
    public function run(){
        $group = Group::standardizeGroupName($this->request->getPar('group'));
        $userGroup = new UserGroup();
        $userGroup -> setGroup($group);
        $userGroups = $userGroup->read(true);
        $ret = array();
        if($userGroups !== null){
            foreach($userGroups as $uGroup){
                $res = $this->forward('/users/user/'.$uGroup->getUserId());
                if($res->getStatusCode() !== 200) Log::err('user: '.$uGroup->getUserId().' not found');
                else $ret[] = json_decode($res->getBody(),true)['User']['username'];
            }
        }
        $this->response->setBody(
            json_encode(array(
                'GroupUsers'=>array(
                    'group'=>$group,
                    'users'=>$ret
                )
            ),JSON_PRETTY_PRINT));
    }
}