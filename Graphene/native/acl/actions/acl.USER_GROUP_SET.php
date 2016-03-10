<?php
namespace acl;

use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;

class UserGroupSet extends Action {
    public function run() {
        $userGroup = UserGroup::getByRequest();
        if ($userGroup->getUserId() !== null) {
            $this->forward('/users/user/' . $userGroup->getUserId());
        } else {
            throw new GraphException('user id is not valid', 400);
        }
        $this->sendModel($userGroup->create());
    }
}