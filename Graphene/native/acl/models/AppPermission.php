<?php
namespace acl;

use Graphene\controllers\exceptions\GraphException;
use Graphene\models\Model;

class AppPermission extends Model{
    public function defineStruct(){
        return array(
            'appId'    =>  Model::UID     .Model::NOT_EMPTY.Model::NOT_NULL,
            'action'   =>  Model::STRING  .Model::MAX_LEN.'200'.Model::NOT_EMPTY.Model::NOT_NULL
        );
    }
    public function onRead(){
        $this->stdAction();
    }
    public function onCreate(){
        $this->stdAction();
    }
    private function stdAction(){
        if(array_key_exists('action',$this->content) && $this->content['action'] !== null){
            $this->content['action']=strtoupper($this->content['action']);
        }
    }
}