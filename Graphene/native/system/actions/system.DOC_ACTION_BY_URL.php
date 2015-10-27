<?php
namespace system;
use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;

class DocActionByUrl extends Action {
    public function run() {
        try{
            $body = json_decode($this->request->getBody(),true)['docRequest'];

        }catch(\Exception $e){
            throw new GraphException('error on request body');
        }

    }
}