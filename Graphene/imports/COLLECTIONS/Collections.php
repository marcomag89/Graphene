<?php
namespace imports;

use Graphene\controllers\Action;
use Graphene\Graphene;
use Graphene\models\ModelCollection;

class ReadCollection extends Action
{
    public function run()
    {
        $model    = new $this->pars[0]();
        $data = $this->request->getData();
        //var_dump($data);

        $query    = $data['search'];
        $sortBy   = (($data['sort']['by']   !== null) ? $data['sort']['by'] : '');
        $pageSize = ((intval($data['page']['size']))? intval($data['page']['size']):null);
        $page     = ((intval($data['page']['no']))? intval($data['page']['no']): 1);
        $sortMode = ((boolval($data['sort']['discend'])) ? 'DSC' : 'ASC');

        $gQuery=[
            'search'=>$query,
            'sort'  =>['by'=>$sortBy,'mode'=>$sortMode]
        ];
        $model->setLazy(true);
        $readed = $model->read(true,$gQuery,$page,$pageSize);

        if($readed instanceof ModelCollection){
            $url  ='http://'.$_SERVER['HTTP_HOST'].Graphene::getInstance()->getSettings()['baseUrl'].$this->request->getUrl();
            $page     = $readed->getPage();
            $pageSize = $readed->getPageSize();

            $httpQ = [
                'search'       => $query,
                'sort_by'      => $sortBy,
                'sort_discend' => (($sortMode==='DSC') ? '1' : '0'),
                'page_size'    => $pageSize,
                'page_no'      => $page
            ];

            $httpQN = $httpQ;
            $httpQN['page_no'] = $httpQN['page_no']+1;

            $httpQP = $httpQ;
            $httpQP['page_no'] = $httpQP['page_no']-1;

            $readed->setNextPageUrl     ($url.'?'.http_build_query($httpQN));
            $readed->setCurrentPageUrl  ($url.'?'.http_build_query($httpQ));
            if($page > 1) $readed->setPreviousPageUrl ($url.'?'.http_build_query($httpQP));
        }
        $this->send($readed);
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