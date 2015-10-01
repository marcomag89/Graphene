<?php
namespace acl;
use Graphene\models\Model;

class App extends Model{

    public function defineStruct()
    {
        return array(
            'apiKey'      => Model::STRING. Model::NOT_EMPTY. Model::NOT_NULL,
            'apiSecret'   => Model::STRING. Model::NOT_EMPTY. Model::NOT_NULL,
            'appName'     => Model::STRING. Model::NOT_EMPTY. Model::NOT_NULL. Model::UNIQUE,
            'appAuthor'   => Model::STRING. Model::NOT_EMPTY. Model::NOT_NULL
        );
    }
    public function onCreate(){
        if($this->getApiKey()===null) $this->generateApiKey();
        if($this->getApiSecret()===null) $this->generateApiSecret();
    }
    private function generateApiKey(){
        $this->setApiKey(md5(uniqid()).md5(uniqid()).md5(uniqid()).md5(uniqid()));
    }
    private function generateApiSecret(){
        $this->setApiSecret(md5(uniqid()));
    }
}