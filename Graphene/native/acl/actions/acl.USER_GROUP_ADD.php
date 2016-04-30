<?php
namespace acl;

use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;

class UserGroupAdd extends Action {
    public function run() {
        $tGroup = $this->request->getData()['UserGroup'];
        $tGroup['group'] = Group::standardizeGroupName($tGroup['group']);
        $groupId = Group::getGroupIdFromName($tGroup['group']);
        $user = $this->forward('/users/user/' . $tGroup['userId'])->getData();
        if ($groupId !== null) {
            $uGroup = new UserGroup();
            $uGroup->setUserId($tGroup['userId']);
            $uGroup->setGroup($groupId);
            $rUGroup = $uGroup->read();
            if ($rUGroup === null) {
                $cUGroup = $uGroup->create();
                $contentG = $cUGroup->getContent();
                $contentG['group'] = $tGroup['group'];
                $this->send(['UserGroup' => $contentG]);
            } else {
                throw new GraphException('Group ' . $tGroup['group'] . ' already assigned to this user');
            }
        } else {
            throw new GraphException('Group ' . $tGroup['group'] . ' does not exists');
        }
    }
}