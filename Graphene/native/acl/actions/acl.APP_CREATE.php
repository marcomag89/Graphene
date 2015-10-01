<?php

namespace acl;
use Graphene\controllers\Action;

class AppCreate extends Action{
    function run(){
        $app = App::getByRequest(true);
        $this->sendModel($app->create());
    }
}