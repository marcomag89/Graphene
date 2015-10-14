<?php
namespace imports;

use Graphene\controllers\Action;
use Graphene\Graphene;
use Graphene\models\ModelCollection;

class ReadCollection extends Action
{
    public function run()
    {
        $model      = new $this->pars[0]();
        $query      = $this->request->getPar('search');
        $sortBy     = $this->request->getPar('sortBy');
        $sortMode   = $this->request->getPar('sortMode');
        $page     = $this->request->getPar('page');
        $pageSize = $this->request->getPar('page_size');

        $httpQ =http_build_query([
            'search'  => $query,
            'sortBy'  => $sortBy,
            'sortMode'=> $sortMode
        ]);

        if($httpQ !== '')$httpQ='?'.$httpQ;

        $gQuery=[
            'search'=>$query,
            'sort'  =>['by'=>$sortBy,'mode'=>$sortMode]
        ];
        $model->setLazy(true);
        $readed = $model->read(true,$gQuery,$page,$pageSize);

        if($readed instanceof ModelCollection){
            $expl = explode('/collection', $this->request->getUrl());
            $url  ='http://'.$_SERVER['HTTP_HOST'].Graphene::getInstance()->getSettings()['baseUrl'].$expl[0].'/collection/';
            $page     = $readed->getPage();
            $pageSize = $readed->getPageSize();
            $readed->setNextPageUrl     ($url.($page+1).'/'.$pageSize.$httpQ);
            $readed->setCurrentPageUrl  ($url.($page).'/'.$pageSize.$httpQ);
            if($page > 1) $readed->setPreviousPageUrl ($url.($page-1).'/'.$pageSize.$httpQ);
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