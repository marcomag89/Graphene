<?php
namespace system;
use Graphene\controllers\Action;
use Graphene\Graphene;

class DocActionByName extends Action {
    public function run() {
        $data=$this->request->getData();
        $action = strtoupper($data['action']);
        $detail = ((array_key_exists('detail', $data) && $data['detail']=='1')? true : false);
        $doc = Graphene::getInstance()->getDoc($action,$detail);
        $this->response->setData(["DocAction"=>$doc]);
    }
}