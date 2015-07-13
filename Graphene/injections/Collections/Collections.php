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

        if($page === null     || $page < 1)     { $page=1;}
        if($pageSize === null || $pageSize < 1) { $pageSize =10;}
        $model->setLazy(true);

        $readed = $model->read(true,null,$page,$pageSize);
        $this->sendModel($readed);
    }
}

class ReadAllCollection extends Action
{
    public function run()
    {
        $model = new $this->pars[0]();
        $model->setLazy(true);

        $readed = $model->read(true,null,1,0);
        $this->sendModel($readed);
    }
}