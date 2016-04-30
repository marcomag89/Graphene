<?php
namespace acl;

use Graphene\controllers\exceptions\GraphException;
use Graphene\models\Model;

class UserGroupOld extends Model {
    public function defineStruct() {
        return [
            'userId' => Model::UID . Model::NOT_EMPTY . Model::NOT_NULL,
            'group'  => Model::UID . Model::NOT_EMPTY . Model::NOT_NULL,
        ];
    }

    public function standardize() {
        $this->content['group'] = Group::standardizeGroupName($this->content['group']);
    }

    public function setContent($content) {
        $this->content = [];
        if (array_key_exists('group', $content)) {
            $this->setGroup($content['group']);
        }
        if (array_key_exists('userId', $content)) {
            $this->content['userId'] = $content['userId'];
        }
        if (array_key_exists('id', $content)) {
            $this->content['id'] = $content['id'];
        }
        if (array_key_exists('version', $content)) {
            $this->content['version'] = $content['version'];
        }
    }

    public function setGroup($group) {
        if ($group === null || $group === '') {
            $this->content['group'] = Group::$everyoneGroupName;
        } else {
            $this->content['group'] = Group::getGroupId($group);
        }
    }

    public function onSend() {
        $this->content['group'] = Group::getGroupName($this->content['group']);
    }

    public function onCreate() {
        $this->securityChecks();
    }

    public function securityChecks() {
        //Check if superuser already assigned
        if ($this->getGroup() === Group::$superUserGroupName) {
            $tUserGroup = new UserGroup();
            $tUserGroup->setGroup(Group::$superUserGroupName);
            if ($tUserGroup->read() !== null) {
                throw new GraphException('Super user group was already assigned', 400);
            }
        }

        \Log::debug('First check pass');

        //Check if already assigned
        if ($this->read() !== null) {
            throw new GraphException('user ' . $this->getUserId() . ' already assigned at ' . $this->getGroup(), 400);
        }
        \Log::debug('Second check pass');

    }

    public function getGroup() {
        return Group::getGroupName($this->content['group']);
    }
}