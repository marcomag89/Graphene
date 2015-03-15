<?php
namespace users;

use Graphene\controllers\Action;
use Graphene\models\Model;

class Validate extends Action
{

    public function run()
    {
        $user = User::getByRequest();
        $readed = $user->read();
        $this->sendModel($readed);
    }
}