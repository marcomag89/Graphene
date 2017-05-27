<?php
namespace acl;

use Graphene\models\Model;

class Group extends Model {

    public static $idPrefix = 'IDGR_';
    public static $superUserGroupName = 'SUPER_USER';
    public static $everyoneGroupName = 'EVERYONE';

    public static function getGroupIdFromName($groupName) {
        if ($groupName == null) {
            return Group::$everyoneGroupName;
        }
        $groupName = Group::standardizeGroupName($groupName);
        try {
            if (!Group::isDefaultGroupName($groupName)) {
                $group = new Group();
                $group->setName($groupName);
                $group = $group->read();
                if ($group !== null) {
                    return $group->getId();
                } else {
                    return null;
                }
            } else {
                return $groupName;
            }
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function standardizeGroupName($groupName) {
        return str_replace(' ', '_', strtoupper($groupName));
    }

    public static function isDefaultGroupName($groupName) {
        return self::standardizeGroupName($groupName) === self::standardizeGroupName(self::$superUserGroupName) || self::standardizeGroupName($groupName) === self::standardizeGroupName(self::$everyoneGroupName);
    }

    public function defineStruct() {
        return [
            'name'   => Model::STRING . Model::MAX_LEN . '200' . Model::NOT_EMPTY . Model::NOT_NULL . Model::UNIQUE,
            'parent' => Model::UID . Model::NOT_NULL . Model::NOT_EMPTY
        ];
    }
}