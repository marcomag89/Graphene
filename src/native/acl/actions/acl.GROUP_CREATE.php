<?php
namespace acl;

use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;

class GroupCreate extends Action {
    public function run() {
        $groupRequest = $this->request->getData()['Group'];
        $groupName = Group::standardizeGroupName($groupRequest['name']);
        $parentGroup = Group::standardizeGroupName(array_key_exists('parent', $groupRequest) ? $groupRequest['parent'] : Group::$everyoneGroupName);
        if (!Group::isDefaultGroupName($groupName) && Group::getGroupIdFromName($groupName) === null) {
            $group = new Group();
            $parentGroupId = Group::getGroupIdFromName($parentGroup);
            if ($parentGroupId === null) {
                throw new GraphException('Parent group does not exists');
            }
            $group->setContent([
                                   "name"   => $groupName,
                                   "parent" => $parentGroupId
                               ]);
            $this->send($group->create());
        } else {
            throw new GraphException('Group ' . $groupName . ' already exists');
        }
    }
}