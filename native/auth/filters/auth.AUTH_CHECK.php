<?php
namespace auth;
use Graphene\controllers\Filter;
use \Log;

class AuthCheck extends Filter{
    public function run (){
        if(self::$cache === null) self::$cache=['session'=>[]];
        $accessToken = $this->request->getHeader('access-token');
        $this->request->setContextPar('session',$this->loadSession($accessToken));
    }
    private function loadSession($accessToken){
        if($accessToken === null)$eAt='anonimous';
        else $eAt=$accessToken;
        if(array_key_exists($eAt,self::$cache['session'])) return self::$cache['session'][$eAt];
        if($accessToken != null){
            $res = $this->forward('/auth/validate/'.$accessToken);
            if($res->getStatusCode() !== 200){
                self::$cache['session'][$eAt]=null;
            }
            else{
                self::$cache['session'][$eAt] = json_decode($res->getBody(),true)['Session'];
            }
        }else {
            self::$cache['session'][$eAt] = null;
        }
        return self::$cache['session'][$eAt];
    }
    private static $cache;
}