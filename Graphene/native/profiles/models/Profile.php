<?php
namespace profiles;
use Graphene\models\Model;

class Profile extends Model{
    public function defineStruct(){
        return [
            "name"     => Model::STRING. Model::NOT_EMPTY. Model::NOT_NULL,
            "surname"  => Model::STRING. Model::NOT_EMPTY. Model::NOT_NULL,
            "userId"   => Model::UID
        ];
    }

}