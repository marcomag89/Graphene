<?php
namespace acl;

use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;

class UserGroupRemove extends Action {
    public function run() {
        $userGroup = $this->request->getData()['UserGroup'];
        $uGroup = new UserGroup();
        $uGroup->setContent([
                                'userId' => $userGroup['userId'],
                                'group'  => Group::getGroupIdFromName($userGroup['group'])
                            ]);
        $result = $uGroup->read();
        if ($result !== null) {
            $result->delete();
            $this->send('user successfully removed from group');
        } else {
            throw new GraphException("Group: " . $userGroup['group'] . " not found for this user");
        }
    }
}