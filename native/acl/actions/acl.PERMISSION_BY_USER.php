<?php
namespace acl;

use Graphene\controllers\Action;

class PermissionByUser extends Action {
    public function run() {
        $userId = $this->request->getPar('userId');
        $groups = $this->forward('/acl/userGroup/byUser/' . $userId)->getData()['UserGroups'];
        $res = [];
        foreach ($groups as $group) {
            $pRes = $this->forward('/acl/permission/' . $group)->getData()['PermissionSet'];
            foreach ($pRes as $permission) {
                if (!in_array($permission, $res)) {
                    $res[] = $permission;
                }
            }
        }
        $this->send(['PermissionSet' => $res]);
    }
}