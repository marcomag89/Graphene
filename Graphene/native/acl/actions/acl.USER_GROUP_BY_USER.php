<?php
namespace acl;

use Graphene\controllers\Action;

class UserGroupByUser extends Action {
    public function run() {
        //TODO rappresentare ricorsioni
        $userId = $this->request->getPar('userId');
        $group = new UserGroup();
        $group->setUserId($userId);
        $userGroups = $group->read(true);
        $ret = [];
        $ret[] = Group::$everyoneGroupName;
        if ($userGroups !== null) {
            foreach ($userGroups as $userGr) {
                $groupName = null;
                if (!Group::isDefaultGroupName($userGr->getGroup())) {
                    $gr = new Group();
                    $gr->setId($userGr->getGroup());
                    $rGroup = $gr->read();
                    if ($rGroup !== null) {
                        $groupName = $rGroup->getName();
                    }
                } else {
                    $groupName = $userGr->getGroup();
                }
                if ($groupName !== null) {
                    $ret[] = $groupName;
                }
            }
        }
        $this->response->setBody(json_encode(['UserGroups' => $ret]));
    }
}