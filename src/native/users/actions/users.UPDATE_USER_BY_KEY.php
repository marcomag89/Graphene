<?php
namespace users;

use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;

class UpdateUserByKey extends Action {

    public function run() {
        $user = User::getByRequest();
        $eUser = new User();
        $eUser->setEditingKey($user->getEditingKey());
        $eUser = $eUser->read();
        if ($eUser !== null) {
            $user->setContent([
                'id'       => $eUser->getId(),
                'version'  => $eUser->getVersion(),
                'username' => $user->getUsername(),
                'password' => $user->getPassword(),
                'email'    => $user->getEmail(),
            ]);
            $updated = $this->forward('/users/user', ['User' => $user->getContent()], 'PUT')->getData();
            //$updated = $updated->generateEditingKey();
            $this->send($updated);
        } else {
            $this->send(new GraphException('Invalid editing key', 403));
        }
    }
}