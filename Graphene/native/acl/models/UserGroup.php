<?php
namespace acl;

use Graphene\models\Model;

class UserGroup extends Model{
    public function defineStruct(){
        return array(
            'userId'   =>  Model::STRING.Model::MAX_LEN.'200'.Model::NOT_EMPTY.Model::NOT_NULL,
            'group'    =>  Model::STRING.Model::MAX_LEN.'200'.Model::NOT_EMPTY.Model::NOT_NULL,
        );
    }
    public function standardize(){
        $this->content['group'] = Group::standardizeGroupName( $this->content['group']);
    }
}