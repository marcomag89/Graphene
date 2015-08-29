<?php
namespace acl;
use Graphene\controllers\exceptions\GraphException;
use Graphene\controllers\Filter;

class AclFilter extends Filter{
    public function run (){
        $this->request->setEnvironmentVar('acl','ok');
    }
}