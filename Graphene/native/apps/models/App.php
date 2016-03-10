<?php
namespace apps;

use Graphene\models\Model;

class App extends Model {

    public function defineStruct() {
        return array(
            'apiKey'    => Model::STRING . Model::NOT_EMPTY . Model::NOT_NULL,
            'apiSecret' => Model::STRING . Model::NOT_EMPTY . Model::NOT_NULL,
            'appName'   => Model::STRING . Model::NOT_EMPTY . Model::NOT_NULL . Model::UNIQUE . Model::SEARCHABLE,
            'appAuthor' => Model::STRING . Model::NOT_EMPTY . Model::NOT_NULL . Model::SEARCHABLE,
        );
    }

    public function onCreate() {
        if ($this->getApiKey() === null) $this->generateApiKey();
        if ($this->getApiSecret() === null) $this->generateApiSecret();
    }

    private function generateApiKey() {
        $this->setApiKey(md5(uniqid()) . md5(uniqid()) . md5(uniqid()) . md5(uniqid()));
    }

    private function generateApiSecret() {
        $this->setApiSecret(md5(uniqid()));
    }

    private function isValidUrl() {
        $url = $this->content['appUrl'];
        return preg_match('/^((([\w\.\-\+]+:)\/{2}(([\w\d\.]+):([\w\d\.]+))?@?(([a-zA-Z0-9\.\-_]+)(?::(\d{1,5}))?))?(\/(?:[a-zA-Z0-9\.\-\/\+\%]+)?)(?:\?([a-zA-Z0-9=%\-_\.\*&;]+))?(?:#([a-zA-Z0-9\-=,&%;\/\\"\'\?]+)?)?)$/', $url);
    }
}