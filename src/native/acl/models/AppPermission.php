<?php
namespace acl;

use Graphene\models\Model;

class AppPermission extends Model {
    public function defineStruct() {
        return [
            'appId'  => Model::UID . Model::NOT_EMPTY . Model::NOT_NULL,
            'action' => Model::STRING . Model::MAX_LEN . '200' . Model::NOT_EMPTY . Model::NOT_NULL
        ];
    }
}