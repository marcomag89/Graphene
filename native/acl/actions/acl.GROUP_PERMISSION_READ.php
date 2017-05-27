<?php
namespace acl;

use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;

class GroupPermissionRead extends Action {
    /**
     * @throws GraphException
     */
    public function run() {
        //First id resolution
        $groupName = Group::standardizeGroupName($this->request->getPar('groupName'));
        if ($groupName === Group::$everyoneGroupName || $groupName === Group::$superUserGroupName) {
            $resolvedPermissions = $this->resolvePermissions($groupName);
        } else {
            $group = new Group();
            $group->setName($groupName);
            $rGroup = $group->read();
            if ($rGroup !== null) {
                $resolvedPermissions = $this->resolvePermissions($rGroup->getId());
            } else {
                throw new GraphException('Invalid group name', 400);
            }
        }
        $this->send(['PermissionSet' => $resolvedPermissions]);

    }

    private function resolvePermissions($groupId) {
        if ($groupId === Group::$everyoneGroupName) {
            //Break case
            $ret = [];
            $perm = new Permission();
            $perm->setGroup($groupId);
            $permissions = $perm->read(true);
            if ($permissions !== null) {
                foreach ($permissions as $permission) {
                    $ret[] = $permission->getAction();
                }
            }

            return $ret;
        } else if ($groupId === Group::$superUserGroupName) {
            //Break case
            return ['*'];
        } else {
            //Recursive case
            $gr = new Group();
            $gr->setId($groupId);
            $group = $gr->read();
            $parentPermissions = $this->resolvePermissions($group->getParent());
            $perm = new Permission();
            $perm->setGroup($groupId);
            $permissions = $perm->read(true);
            if ($permissions !== null) {
                foreach ($permissions as $permission) {
                    $parentPermissions[] = $permission->getAction();
                }
            }

            return $parentPermissions;
        }
    }
}