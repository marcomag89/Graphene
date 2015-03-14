<?php
namespace auth;

use Graphene\models\Model;
use Graphene\controllers\model\ModelController;
use users\User;
use Graphene\controllers\exceptions\GraphException;
use Graphene\controllers\exceptions\ExceptionCodes;

class AuthRequest extends Model
{

    public function defineStruct()
    {
        return array(
            'apiKey' => Model::MATCH . "/^[A-Za-z0-9]+$/" . Model::NOT_NULL . Model::MAX_LEN . '150',
            'apiSecret' => Model::MATCH . "/^[A-Za-z0-9]+$/" . Model::NOT_NULL . Model::MAX_LEN . '80',
            'user' => array(
                'email' => Model::STRING . Model::NOT_NULL,
                'password' => Model::STRING . Model::NOT_NULL
            )
        );
    }

    public function getUser()
    {
        $user = new User();
        $user->setEmail($this->content['user']['email']);
        $user->setPassword($this->content['user']['password']);
        if (! $user->isValid())
            throw new GraphException('syntax of email or password is invalid', ExceptionCodes::BEAN_CONTENT_VALID, 400);
        return $user;
    }
}