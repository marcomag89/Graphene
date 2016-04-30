<?php
namespace acl;

use Graphene\controllers\Action;

class GroupPermissionAdd extends Action {
    public function run() {
        $permission = $this->request->getData()['Permission'];
        $pGroup = Group::standardizeGroupName($permission['group']);
        $groupId = null;
        if (!Group::isDefaultGroupName($permission['group'])) {
            $group = new Group();
            $group->setName($pGroup);
            $rGroup = $group->read();
            if ($rGroup !== null) {
                $groupId = $rGroup->getId();
            } else {
                throw new GraphException('Invalid group name: ' . $permission['group']);
            }
        } else {
            $groupId = $pGroup;
        }
        $p = new Permission();
        $p->setContent([
                           "group"  => $groupId,
                           "action" => $permission['action']
                       ]);
        $p->create();
        $this->send([
                        'Permission' => [
                            'group'  => $pGroup,
                            'action' => $permission['action']
                        ]
                    ]);
    }
}