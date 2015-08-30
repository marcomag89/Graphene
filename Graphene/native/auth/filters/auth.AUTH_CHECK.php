<?php
namespace auth;
use Graphene\controllers\Filter;

class AuthCheck extends Filter{
    public function run (){
        $this->request->setContextPar('auth','ok');
    }
}