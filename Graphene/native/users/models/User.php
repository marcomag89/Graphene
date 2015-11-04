<?php
namespace users;

use Graphene\models\Model;
use Graphene\controllers\model\ModelController;
use Graphene\controllers\exceptions\GraphException;

class User extends Model{
    public function defineStruct(){
        //$mailMatch="/^[a-z0-9!#$%&'*+\\/=?^_`{|}~-]+(?:.[a-z0-9!#$%&'*+\\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/";
        return array(
            'username' => Model::STRING. Model::MIN_LEN.'4'. Model::MAX_LEN.'42'. Model::NOT_NULL. Model::NOT_EMPTY. Model::UNIQUE. Model::SEARCHABLE,
            'password' => Model::STRING. Model::NOT_NULL
        );
    }

    public function onCreate(){
        if (! $this->checkPassword())
            throw new GraphException('Password requires one upper and one number 6-25 chars', 4001, 400);
        $this->encryptPassword();
    }

    public function onSend(){
        $this->unsetPassword();
    }

    public function onRead(){
        $this->encryptPassword();
    }

    public function onUpdate(){
       $this->onCreate();
    }

    /**
     * Regole password
     * - Almeno di 8 caratteri
     * - Contiene almeno una lettera maiuscola
     * - Contiene almeno una lettera minuscola
     * - Contiene almeno un numero
     */
    public function checkPassword(){
        $pwd = $this->content['password'];
        return preg_match("/^(?=.*[^a-zA-Z])(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])\w{8,32}$/", $pwd);
    }

    public function unsetPassword(){
        if (isset($this->content['password']))
            unset($this->content['password']);
    }

    public function encryptPassword(){
        if (isset($this->content['password']))
            $this->content['password'] = md5($this->content['password']);
    }

    public function getReadActionStruct(){
        $str = json_decode(json_encode($this->getStruct()),true);
        unset($str['password']);
        return $str;
    }
}