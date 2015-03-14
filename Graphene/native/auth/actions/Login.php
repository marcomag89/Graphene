<?php
namespace auth;

use Graphene\controllers\Action;
use Graphene\controllers\http\GraphRequest;
use auth\AuthRequest;
use users\User;
use auth\Session;

class Login extends Action
{

    public function run()
    {
        $auth = AuthRequest::getByRequest();
        // TODO Controllo con Request forwarding api_key e api_secret.
        // IF APIKEY/SECRET
        $apiKey = $auth->getApiKey();
        $user = $auth->getUser();
        $res = $this->forward('/users/validate', $user->serialize());
        if ($res->getStatusCode() != 200) {
            $this->sendError(403, 'email or password invalid');
            return;
        }
        // Creazione della sessione
        $session = new Session();
        $user = json_decode($res->getBody(), true)['User'];
        $session->setHostAddress($this->request->getIp());
        $session->setHostAgent($this->request->getUserAgent());
        $session->setApiKey($apiKey);
        $session->setEnabled(true);
        $session->createTimestamp();
        $session->createAccessToken();
        $session->setUser($user['id']);
        $created = $session->create()[0];
        $this->sendModel($created);
    }
}