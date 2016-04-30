<?php
namespace acl;

use Graphene\models\Model;

class UserGroup extends Model {
    public function defineStruct() {
        return [
            'userId' => Model::UID . Model::NOT_EMPTY . Model::NOT_NULL,
            'group'  => Model::UID . Model::NOT_EMPTY . Model::NOT_NULL,
        ];
    }
}