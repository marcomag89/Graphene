<?php
namespace system;
use Graphene\controllers\Action;
use Graphene\Graphene;

class DocActionByName extends Action {
    public function run() {
        $action = strtoupper($this->request->getData()['action']);
        $doc = Graphene::getInstance()->getDoc($action);
        $this->response->setData(["DocAction"=>$doc]);
    }
}