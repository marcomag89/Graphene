<?php
namespace users;

use Graphene\controllers\Action;

class CreateExternal extends Action {

    public function run() {

        $user = new User();
        $user->setEmail($this->request->getData()['UserExternal']['email']);
        $user->setUsername('ext' . uniqid());
        $user->setPassword('Ext' . uniqid() . '01');
        $created = $this->forward('/users/user', ['User' => $user->getContent()])->getData();
        $userData = $this->forward('/users/sendEditMail', [
            'ResetMail' => [
                'email'    => $created['User']['email'],
                'template' => $this->request->getData()['UserExternal']['template']
            ]
        ])->getData();
        //Graphene::getLogger()->debug($userData);
        $this->send($userData);
    }
}
