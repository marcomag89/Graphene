<?php
namespace Graphene\db\drivers\mysql;


class StorageRequest
{
    public function __construct($json, $connectionManager,$args = null){
        $this->connectionManager = $connectionManager;
        if(is_string($json))$json = json_decode($json,true);
        $this->json   = $json;
        $this->domain = $this->json['domain'];
        $this->requestSettings = $this->processArgs($args,$this->json['page'],$this->json['pageSize']);
        $this->model = new RequestModel($this->json['struct'],$this->json['content'],$this->domain,$this->connectionManager);
    }

    /**
     * Adatta gli argomenti ricevuti nella forma attesa:
     *{
     *  search  : 'stringa di ricerca' | null,
     *  compare : {fieldA:lt, fieldB:gt}| null
     *  where   : 'stringa della where custom' | null,
     *  sort    : {by:'', mode:'ASC/DSC'}  | null'
     * }
     *
     * @param $args
     * @param $page
     * @param $pageSize
     * @return array
     */
    private function processArgs($args, $page=null, $pageSize=null){
        if(is_string($args)){
            $ret = [];
            $ret['where'] = $args;
        } else if(is_array($args)){
            $ret = $args;
        } else if(is_null($args)){
            $ret=[];
        }
        $ret['page']     = intval($page);
        $ret['pageSize'] = intval($pageSize);
        return $ret;
    }

    public function getOperator($field){
        if(
            array_key_exists('compare',$this->requestSettings) &&
            array_key_exists($field,$this->requestSettings['compare'])
        ){
            switch(strtolower($this->requestSettings['compare'][$field])){
                case 'lt'  : return '<';
                case 'gt'  : return '>';
                case 'eq'  : return '=';
                case 'gte' : return '>=';
                case 'lte' : return '>=';
                case 'neq' : return '<>';
                default    : return '=';
            }
        }else return '=';
    }

    public function hasSearch(){
        return (
            array_key_exists('search',$this->requestSettings) &&
            $this->requestSettings['search']!== null
        );
    }
    public function hasWhere(){
        return (
            array_key_exists('where',$this->requestSettings) &&
            $this->requestSettings['where']!== null
        );
    }
    public function hasSort(){
        return (
            array_key_exists('sort',$this->requestSettings)                    &&
            array_key_exists('by',$this->requestSettings['sort'])              &&
            $this->getModel()->haveField($this->requestSettings['sort']['by'])
        );
    }
    public function getSortSettings(){
        return $this->requestSettings['sort'];
    }
    public function getSearchTherms(){
        return (explode(' ',$this->requestSettings['search']));
    }

    public function getModel(){
        return $this->model;
    }

    public function getDomain(){
        return $this->domain;
    }

    public function getRequestSettings(){
        return $this->requestSettings;
    }
    public function serializeResponse($data){
        $res = json_decode(json_encode($this->json),true);
        unset ($res['content']);
        $res['collection']=[];
        foreach($data as $item){
            $res['collection'][] = $item;
        }
        return json_encode($res,JSON_PRETTY_PRINT);
    }

    public function cloneForSingleRead(){
        $nJson = json_decode(json_encode($this->json),true);
        $tempId = $nJson['content']['id'];
        unset ($nJson['content']);
        $nJson['content']['id'] = $tempId;
        $nJson['page']     = self::DEFAULT_PAGE;
        $nJson['pageSize'] = self::DEFAULT_PAGE_SIZE;
        return $nJson;
    }

    private $domain;
    private $requestSettings;
    private $json;
    private $connectionManager;

    /**
     * @var RequestModel
     */
    private $model;
}