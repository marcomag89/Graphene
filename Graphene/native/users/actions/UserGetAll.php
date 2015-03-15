<?php
namespace users;

use Graphene\controllers\Action;
use users\User;

class AllUsers extends Action
{

    public function run()
    {
        $user = new user();
        $users = $user->read(true);
        $this->sendModel($users);
    }
}