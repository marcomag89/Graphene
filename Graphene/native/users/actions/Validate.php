<?php
namespace users;

use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;
use Graphene\models\Model;

class Validate extends Action
{

    public function run()
    {
        $user = User::getByRequest();
        if($user->getUsername() !== null && $user->getPassword()!==null){
            $readed = $user->read();
            $this->sendModel($readed);
        }else{
            throw new GraphException('bad request',400);
        }
    }
}