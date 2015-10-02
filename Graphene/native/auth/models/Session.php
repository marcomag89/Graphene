<?php
namespace auth;

use Graphene\models\Model;
use \DateTime;

class Session extends Model
{

    public function defineStruct()
    {
        $lazy = array();
        $lazy['hostAddress'] = Model::STRING .   Model::NOT_EMPTY;
        $lazy['hostAgent']   = Model::STRING .   Model::MAX_LEN . '256' . Model::NOT_EMPTY;
        $lazy['apiKey']      = Model::STRING.    Model::MAX_LEN.'256' . Model::NOT_EMPTY;
        $lazy['enabled']     = Model::BOOLEAN .  Model::NOT_EMPTY;
        $lazy['time']        = Model::DATETIME.  Model::NOT_EMPTY;
        $lazy['user']        = Model::UID.       Model::NOT_EMPTY;
        $lazy['accessToken'] = Model::MATCH."/^[A-Za-z0-9]+$/" . Model::NOT_EMPTY . Model::MAX_LEN . '120' . Model::UNIQUE;
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
        $this->content['accessToken'] = md5(md5($this->content['hostAddress']) . md5($this->content['apiKey'])) . md5(md5($this->content['hostAgent']) . md5(microtime(false)));
    }

    public function createDatetime()
    {
        $date = new DateTime();
        $this->content['time'] = $date->format('Y-m-d H:i:s');
    }
}