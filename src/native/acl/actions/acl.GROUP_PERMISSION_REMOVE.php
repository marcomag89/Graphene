<?php
namespace acl;

use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;

class GroupPermissionRemove extends Action {
    public function run() {
        $permission = $this->request->getData()['Permission'];
        $groupId = null;
        if (Group::isDefaultGroupName($permission['group'])) {
            $groupId = $permission['group'];
        } else {
            $group = new Group();
            $group->setName($permission['group']);
            $group = $group->read();
            if ($group !== null) {
                $groupId = $group->getId();
            } else {
                throw new GraphException('Invalid group name: ' . $permission['group']);
            }
        }

        $oPermission = new Permission();
        $oPermission->setAction($permission['action']);
        $oPermission->setGroup($groupId);
        $oPermission = $oPermission->read();
        if ($oPermission !== null) {
            $oPermission->delete();
            $this->sendMessage('permission ' . $permission['action'] . ' removed from ' . $permission['group']);
        } else {
            throw new GraphException('Permission ' . $permission['action'] . ' not found for group ' . $permission['group']);
        }

    }
}