<?php
namespace auth;

use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;

class Validate extends Action {
    public function run() {
        $token = $this->request->getPar('at');
        $session = new Session();
        $session->setAccessToken($token);
        $result = $session->read();

        if ($result === null) {
            throw new GraphException('Access token not valid', 404);
        }
        if ($result->getEnabled() === false) {
            throw new GraphException('Session is closed', 400);
        }
        $this->sendModel($result);
    }
}
