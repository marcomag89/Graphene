<?php
namespace acl;

use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;

class UserRemove extends Action {
    public function run() {
        $data = $this->request->getData();
        if (!array_key_exists('User', $data) || !array_key_exists('id', $data['User']))
            throw new GraphException('unsupported request for USER_REMOVE', 400);

        $userId = $data['User']['id'];
        $uGroupProto = new UserGroup();
        $uGroupProto->setUserId($userId);
        $userGroups = $uGroupProto->read(true, null, 1, 0);
        foreach ($userGroups as $userGroup) {
            $userGroup->delete();
        }
        $this->send('User removed successfully');
    }
}