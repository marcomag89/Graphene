<?php
namespace users;

use Graphene\controllers\interfaces\StdRead;
use Graphene\models\Model;

class Read extends StdRead {

    protected function getModelInstance() {
        return new User();
    }

    protected function formatReadedModel(Model $user) {
        if ($user !== null) {
            $userC = $user->getContent();
            unset($userC['password']);
            unset($userC['editingKey']);
            $user = new User();
            $user->setContent($userC);
        }

        return $user;
    }
}