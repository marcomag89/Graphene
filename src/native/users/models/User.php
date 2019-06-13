<?php
namespace users;

use Graphene\controllers\exceptions\GraphException;
use Graphene\models\Model;

class User extends Model {
    public function defineStruct() {
        //$mailMatch="/^[a-z0-9!#$%&'*+\\/=?^_`{|}~-]+(?:.[a-z0-9!#$%&'*+\\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/";
        return [
            'username'   => Model::STRING . Model::MIN_LEN . '4' . Model::MAX_LEN . '42' . Model::NOT_NULL . Model::NOT_EMPTY . Model::UNIQUE . Model::SEARCHABLE,
            'password'   => Model::STRING . Model::NOT_NULL,
            'email'      => Model::STRING . Model::MAX_LEN . '200' . Model::NOT_NULL . Model::UNIQUE,
            'editingKey' => Model::STRING . Model::MAX_LEN . '200' . Model::UNIQUE
        ];
    }

    public function generateEditingKey() {
        $rUser = new User();
        $rUser->setId($this->getId());
        $rUser = $rUser->read();
        if ($rUser !== null) {
            $this->setContent($rUser->getContent());
            $eKey = uniqid() . uniqid() . uniqid();
            $this->content['editingKey'] = $eKey;
            $updated = $this->update();

            return $updated;
        }
    }

    /**
     * Regole password
     * - Almeno di 8 caratteri
     * - Contiene almeno una lettera maiuscola
     * - Contiene almeno una lettera minuscola
     * - Contiene almeno un numero
     */
    public function checkPassword() {
        $pwd = $this->content['password'];

        return preg_match("/^(?=.*[^a-zA-Z])(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])\w{8,32}$/", $pwd);
    }

    /**
     * Validazione email
     */
    public function checkEmail() {
        $mail = $this->content['email'];

        return preg_match('/^[\w\.\+]*@[\w\.]*$/', $mail);
    }

    public function encryptPassword() {
        if (isset($this->content['password'])) {
            $this->content['password'] = md5($this->content['password']);
        } else {
            throw new GraphException("Password is not set");
        }
    }

    public function getReadActionStruct() {
        $str = json_decode(json_encode($this->getStruct()), true);
        unset($str['password']);

        return $str;
    }
}
