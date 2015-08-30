<?php
namespace acl;
use Graphene\controllers\exceptions\GraphException;
use Graphene\controllers\Filter;

class AclCheck extends Filter{
    public function run (){
        $this->request->setEnvironmentVar('acl','ok');
    }
}