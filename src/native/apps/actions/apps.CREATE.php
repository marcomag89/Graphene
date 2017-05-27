<?php

namespace apps;
use Graphene\controllers\Action;

class Create extends Action{
    function run(){
        $app = App::getByRequest(true);
        $this->sendModel($app->create());
    }
}