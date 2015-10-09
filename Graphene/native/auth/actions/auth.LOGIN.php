<?php
namespace auth;

use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;
use Graphene\controllers\http\GraphRequest;
use auth\AuthRequest;
use users\User;
use auth\Session;

class Login extends Action
{

    public function run()
    {
        $apiKey = $this->request->getContextPar('acl-app-info')['apiKey'];
        $userData = json_decode($this->request->getBody(),true)['User'];
        $user = new User();
        $user->setEmail($userData['email']);
        $user->setPassword($userData['password']);
        $res  = $this->forward   ('/users/validate', $user->serialize());
        if ($res->getStatusCode() !== 200) throw new GraphException('email or password invalid',403);

        // Creazione della sessione
        $session = new Session();
        $user = json_decode($res->getBody(), true)['User'];
        $session -> setHostAddress($this->request->getIp());
        $session -> setHostAgent($this->request->getUserAgent());
        $session -> setApiKey($apiKey);
        $session -> setEnabled(true);
        $session -> createDatetime();
        $session -> createAccessToken();
        $session -> setUser($user['id']);
        $created = $session->create();

        $this->sendModel($created);
    }
}