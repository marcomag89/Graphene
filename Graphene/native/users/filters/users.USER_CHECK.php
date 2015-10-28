<?php
namespace users;
use Graphene\controllers\Filter;
use \Log;

class UserCheck extends Filter{
    public function run (){
        if(self::$cache === null)self::$cache = [];
        $session = $this->request->getContextPar('session');
        if($session !== null && $session['user'] !== null){
            $user = $this->loadUser($session['user']);
            $this->request->setContextPar('user',$user);
        }else{
            $this->request->setContextPar('user',null);
        }
    }
    private function loadUser($user){
        if(array_key_exists($user, self::$cache)) self::$cache[$user];
        else{
            $res = $this->forward('/users/user/'.$user);
            if($res->getStatusCode() === 200){
                self::$cache[$user]=json_decode($res->getBody(),true)['User'];
            }
        }
        return self::$cache[$user];
    }
    private static $cache;
}