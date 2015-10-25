<?php
namespace users;
use Graphene\controllers\Filter;
use \Log;

class UserCheck extends Filter{
    public function run (){
        $session = $this->request->getContextPar('session');
        if($session !== null && $session['user'] !== null){
           $res = $this->forward('/users/user/'.$session['user']);
            if($res->getStatusCode() === 200){
                $this->request->setContextPar('user',json_decode($res->getBody(),true)['User']);
            }else{//Log::err('user id is not valid');
            }
        }
    }
}