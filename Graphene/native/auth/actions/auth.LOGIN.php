<?php
namespace auth;

use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;
use users\User;

class Login extends Action
{

    public function run()
    {
        $apiKey = $this->request->getContextPar('acl-app-info')['apiKey'];

        $userData = json_decode($this->request->getBody(),true)['User'];
        $user = new User();
        $user->setUsername($userData['username']);
        $user->setPassword($userData['password']);

        try{$res = $this->forward('/users/validate', $user->serialize());}
        catch(GraphException $e){throw new GraphException('username or password invalid',403);}

        // Creazione della sessione
        $session = new Session();
        $user    = json_decode($res->getBody(), true)['User'];
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

    public function getRequestStruct(){
        $user=new User();
        return ['User'=>$user->defineStruct()];
    }

    public function getResponseStruct(){
        $session=new Session();
        return ['User'=>$session->getStruct()];
    }
}