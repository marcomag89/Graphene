<?php
namespace injection;

use Graphene\controllers\Action;

class ReadCollection extends Action
{
    public function run()
    {
        $model = new $this->pars[0]();
        $page     = $this->request->getPar('page');
        $pageSize = $this->request->getPar('page_size');
        //print_r($this->request->getPars());
        //echo $pageSize."\n";
        if($page === null)     { $page=1;}
        if($pageSize === null) { $pageSize =10;}
        $model->setLazy(true);

        $readed = $model->read(true,null,$page,$pageSize);
        $this->sendModel($readed);
    }
}