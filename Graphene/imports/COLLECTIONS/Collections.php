<?php
namespace imports;

use Graphene\controllers\Action;
use Graphene\Graphene;
use Graphene\models\Model;
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

    public function getRequestStruct(){
        return [
            'search' => Model::STRING,
            'sort' =>[
                'by'      => Model::STRING,
                'discend' => Model::BOOLEAN
            ],
            'page' =>[
                'size' => Model::INTEGER,
                'no'   => Model::INTEGER
            ]
        ];
    }

    public function getResponseStruct(){
        $model = new $this->pars[0]();
        if($model instanceof Model){
            return [
                'Collection' => [[$model->getModelName() => $model->getReadActionStruct()]],
                'cursor'     => [
                    'nxt'=>Model::STRING,
                    'cur'=>Model::STRING,
                    'prv'=>Model::STRING
                ]
            ];
        }
    }
    public function getActionInterface(){
        $model  = new $this->pars[0]();
        $struct = [$model->getModelName() => $model->getReadActionStruct()];
        $flatStructArr = $this->contentToFlatArray($struct);
        $flatStruct=[];
        foreach($flatStructArr as $k=>$fieldStruct){
            $flatStructArr[$k]=explode(Model::CHECK_SEP,$fieldStruct);
            $flatStruct[$k]=[];
            foreach($flatStructArr[$k] as $check){
                if($check !== ''){
                    $flatStruct[$k][]=$check;
                }
            }
        }
        return [
            "name"             => "GCI",
            "item-struct"      => $struct,
            "item-flat-struct" => $flatStruct
        ];
    }

    private function contentToFlatArray($content, &$path = '', &$schema = null){
        if ($schema == null) $schema = array();
        foreach ($content as $key => $value) {
            if (strcmp($path, '') == 0) $tmpPath = $key;
            else $tmpPath = $path . '_' . $key;

            if (is_array($value) && $content != NULL) $this->contentToFlatArray($value, $tmpPath, $schema);
            else {$schema[$tmpPath] = $value;}
        }
        return $schema;
    }

    public function getDescription(){
        $model = new $this->pars[0]();
        $modName = $model->getModelName();
        return "# Read ".$modName." collection\n this action allows to read collection of ".$modName." instances, implementing **GCI** (*Graphene Collection Interface*).\nThis interface is automatically paged and you can quest this action using search and sort url parameters\n\n* **page_no** page selector, default is **1**\n* **page_size** allows to select a page size, default is **20**\n* **search** search string default is an empty string\n* **sort_by** set this parameter with name of field\n* **sort_discend** if value is '1' sort will bi discend default **0**\n\n".
        parent::getDescription();
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