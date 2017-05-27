<?php
namespace users;

use Graphene\controllers\exceptions\GraphException;
use Graphene\controllers\interfaces\StdCreate;
use Graphene\models\Model;

class Create extends StdCreate {
    function getModelInstance() {
        return new User();
    }

    protected function getModelFromRequest() {
        $user = User::getByRequest();
        if ($user instanceof User) {
            if (!$user->checkPassword()) throw new GraphException('Password requires one upper and one number 6-25 chars', 4001, 400);
            if (!$user->checkEmail()) throw new GraphException('Invalid email', 4002, 400);

            $user->encryptPassword();
            $user->checkEmail();
            return $user;
        } else {
            throw new GraphException('internal server error, user mismatch');
        }
    }

    protected function formatCreatedModel($user) {
        $userC = $user->getContent();
        unset($userC['password']);
        unset($userC['editingKey']);
        $user = new User();
        $user->setContent($userC);
        return $user;
    }
}