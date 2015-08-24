<?php
namespace acl;

use Graphene\models\Model;

class Permission extends Model{
    public function defineStruct(){
        return array(
            'group'   =>  Model::STRING.Model::MAX_LEN.'200'.Model::NOT_EMPTY.Model::NOT_NULL,
            'action'  =>  Model::STRING.Model::MAX_LEN.'200'.Model::NOT_EMPTY.Model::NOT_NULL
        );
    }
    public function standardize(){
        $this->content['group']   = strtoupper($this->content['group']);
        $this->content['action']  = strtoupper($this->content['action']);
    }
}