<?php
namespace system;
use acl\Group;
use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;
use Graphene\Graphene;

class Stat extends Action {

    public function run() {
        $req = json_decode($this->request->getBody(),true)['Stat'];
        if ($req !== null) {
            $method  = $req['method'];
            $url     = $req['url'];
            $body    = $req['body'];
            $res=$this->forward($url,json_encode($body),$method);
            $this->response->setBody(
                json_encode([
                    "response" => json_decode($res->getBody(),true),
                    "Stats"    => Graphene::getInstance()->getStats()
                ],JSON_PRETTY_PRINT));
        } else {
            $this->sendError(400,'invalid request',400);
        }//end if

    }//end run

}//end class