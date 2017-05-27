<?php
namespace users;

use Graphene\controllers\Action;
use Graphene\Graphene;

class ValidateEditingKey extends Action {

    public function run() {
        $user = new User();
        $user->setEditingKey($this->request->getPar('key'));
        $this->send($user->read());
    }
}