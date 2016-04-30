<?php
namespace acl;

use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;

class GroupUpdate extends Action {
    public function run() {
        $group = $this->request->getData()['Group'];
        $group['name'] = Group::standardizeGroupName($group['name']);
        $group['parent'] = $this->getParentId(Group::standardizeGroupName(array_key_exists('parent', $group) ? $group['parent'] : Group::$everyoneGroupName));

        if (!$this->checkGroupDefault($group['name']) && !$this->groupExists($group['name'], $group['id'])) {
            $groupModel = new Group();
            $groupModel->setContent($group);
            $this->send($groupModel->update());
        } else {
            throw new GraphException('Group ' . $group['name'] . ' already exists');
        }
    }

    private function getParentId($parentGroupName) {
        if ($this->checkGroupDefault($parentGroupName)) {
            return $parentGroupName;
        } else {
            $group = new Group();
            $group->setName($parentGroupName);
            $readedGroup = $group->read();
            if ($readedGroup === null) {
                throw new GraphException('Parent group does not exists');
            }

            return $readedGroup->getId();
        }
    }

    private function checkGroupDefault($groupName) {
        return Group::standardizeGroupName($groupName) === Group::$everyoneGroupName || Group::standardizeGroupName($groupName) === Group::$superUserGroupName;
    }

    private function groupExists($groupName, $groupId) {
        try {
            $foundGroup = $this->forward('/acl/group/' . $groupName)->getData();

            return array_key_exists('Group', $foundGroup) && !$foundGroup['Group']['id'] === $groupId;
        } catch (\Exception $e) {
            return false;
        }
    }
}