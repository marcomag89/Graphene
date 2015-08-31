<?php
namespace auth;
use Graphene\controllers\Filter;
use \Log;

class AuthCheck extends Filter{
    public function run (){
        $accessToken=$this->request->getHeader('access-token');
        if($accessToken != null){
            $res = $this->forward('/auth/validate/'.$accessToken);
            if($res->getStatusCode() !== 200) Log::err('access token is not valid');
            else{
                $this->request->setContextPar('session', json_decode($res->getBody(),true)['Session']);
            }
        }
    }
}