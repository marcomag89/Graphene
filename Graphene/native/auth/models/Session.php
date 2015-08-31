<?php
namespace auth;

use Graphene\models\Model;
use Graphene\controllers\model\ModelController;

class Session extends Model
{

    public function defineStruct()
    {
        $lazy = array();
        $lazy['hostAddress'] = Model::STRING . Model::NOT_EMPTY;
        $lazy['hostAgent']   = Model::STRING . Model::MAX_LEN . '256' . Model::NOT_EMPTY;
        $lazy['apiKey']      = Model::UID . Model::NOT_EMPTY;
        $lazy['enabled']     = Model::BOOLEAN . Model::NOT_EMPTY;
        $lazy['timeStamp']   = Model::INTEGER . Model::MAX_LEN . '40' . Model::NOT_EMPTY;
        $lazy['accessToken'] = Model::MATCH . "/^[A-Za-z0-9]+$/" . Model::NOT_EMPTY . Model::MAX_LEN . '120' . Model::UNIQUE;
        $lazy['user']        = Model::UID . Model::NOT_EMPTY;
        return $lazy;
    }

    public function unsetForLogout()
    {
        unset($this->content['hostAddress']);
        unset($this->content['hostAgent']);
        unset($this->content['apiKey']);
        unset($this->content['enabled']);
        unset($this->content['timeStamp']);
        unset($this->content['user']);
    }

    public function createAccessToken()
    {
        $this->content['accessToken'] = md5(md5($this->content['hostAddress']) . md5($this->content['apiKey'])) . md5(md5($this->content['hostAgent']) . md5($this->content['timeStamp']));
    }

    public function createTimestamp()
    {
        $this->content['timeStamp'] = round(microtime(true) * 1000);
    }
}