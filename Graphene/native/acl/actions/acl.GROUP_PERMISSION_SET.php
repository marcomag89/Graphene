<?php
namespace acl;

use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;

class GroupPermissionSet extends Action {
    public function run() {
        $permissionProto = $this->request->getData()['Permission'];
        if (!array_key_exists('group', $permissionProto) || !array_key_exists('permissions', $permissionProto))
            throw new GraphException('Invalid permission set request', 400);
        $errs = [];
        $permissions = $permissionProto['permissions'];
        $group = $permissionProto['group'];
        $permissionList = $this->forward('/acl/permission/' . $group)->getData()['PermissionSet'];
        $doDelete = $this->getMissedPermissions($permissions, $permissionList);
        $doAdd = $this->getMissedPermissions($permissionList, $permissions);
        foreach ($doAdd as $pDoAdd) {
            try {
                $this->forward('/acl/permission', [
                    "Permission" => [
                        "group"  => $group,
                        "action" => $pDoAdd
                    ]
                ]);
            } catch (\Exception $e) {
                $errs[] = $e->getMessage();
            }
        }

        foreach ($doDelete as $pDoDel) {
            try {
                $this->forward('/acl/permission', [
                    "Permission" => [
                        "group"  => $group,
                        "action" => $pDoDel
                    ]
                ], 'DELETE');
            } catch (\Exception $e) {
                $errs[] = $e->getMessage();
            }
        }
        $res = $this->forward('/acl/permission/' . $group)->getData();
        $res['errors'] = $errs;
        $this->send($res);
    }

    private function getMissedPermissions($container, $permissions) {
        $ret = [];
        foreach ($permissions as $permission) {
            if (!in_array($permission, $container)) {
                $ret[] = $permission;
            }
        }

        return $ret;
    }
}