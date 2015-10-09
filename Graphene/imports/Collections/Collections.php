<?php
namespace imports;

use Graphene\controllers\Action;
use Graphene\Graphene;
use Graphene\models\ModelCollection;

class ReadCollection extends Action
{
    public function run()
    {
        $model = new $this->pars[0]();
        $page     = $this->request->getPar('page');
        $pageSize = $this->request->getPar('page_size');
        $undPage = false;
        $undSize = false;
        if($page === null     || $page < 1)     { $undPage = true; $page = 1;}
        if($pageSize === null || $pageSize < 1) { $undSize = true; $pageSize =10;}
        $model->setLazy(true);
        $readed = $model->read(true,null,$page,$pageSize);
        if($readed instanceof ModelCollection){
            $expl = explode('/collection', $this->request->getUrl());
            $url  ='http://'.$_SERVER[HTTP_HOST].Graphene::getInstance()->getSettings()['baseUrl'].$expl[0].'/collection/';
            $readed->setNextPageUrl     ($url.($page+1).'/'.$pageSize);
            $readed->setCurrentPageUrl  ($url.($page).'/'.$pageSize);

            if($page > 1)
                $readed->setPreviousPageUrl ($url.($page-1).'/'.$pageSize);
        }
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