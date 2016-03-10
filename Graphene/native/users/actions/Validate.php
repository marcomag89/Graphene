<?php
namespace users;

use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;

class Validate extends Action {

    public function run() {
        $user = User::getByRequest();
        if ($user->getUsername() !== null && $user->getPassword() !== null) {
            $user->encriptPassword();
            $readed = $user->read();
            if ($readed === null) {
                $emailUser = new User();
                $emailUser->setEmail($user->getUsername());
                $readed = $emailUser->read();
            }
            if ($readed !== null) {
                $this->sendModel($readed);
            } else {
                throw new GraphException('invalid username or password', 400);
            }
        } else {
            throw new GraphException('bad request', 400);
        }
    }
}