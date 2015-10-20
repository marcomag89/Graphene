<?php
namespace system;
use Graphene\controllers\Action;
use Graphene\Graphene;

class DocActionByName extends Action {
    public function run() {
        $action = $this->request->getPar('action');
        $action = strtoupper(str_replace('__','.',$action));
        $doc = Graphene::getInstance()->getDoc($action);
        $this->response->setBody(json_encode(["DocAction"=>$doc],JSON_PRETTY_PRINT));
    }
}