<?php
namespace Graphene\models;

class ModelCollection implements \Iterator, \Serializable
{

    public function __construct(Model $model)
    {
        $this->acceptedClass = get_class($model);
        $this->content = [];
    }
    public function setPage($page){
        $this->pageNo=$page;
    }
    public function setPageSize($pageSize){
        $this->pageSize=$pageSize;
    }

    public function getPage()      {return $this->pageNo;  }
    public function getPageSize()  {return $this->pageSize;}

    public function add($model)
    {
        if (! is_array($model)) {
            $models = array(
                $model
            );
        } else {
            $models = $model;
        }
        //$this->rewind();
        // Check type
        foreach ($models as $mod) {
            if ($mod instanceof $this->acceptedClass) {
                $this->content[] = $mod;
            }
        }
    }

    public function remove($index)
    {
        if (isset($this->content[$index]))
            array_slice($this->content, $index);
    }
    // Iterator functions
    public function current(){
        $var = current($this->content);
        //echo "current: $var\n";
        return $var;
    }

    public function next()
    {
        $var = next($this->content);
        //echo "next: $var\n";
        return $var;
    }

    public function key()
    {
        $var = key($this->content);
        //echo "key: $var\n";
        return $var;
    }

    public function valid()
    {
        $key = key($this->content);
        $var = ($key !== NULL && $key !== FALSE);
        return $var;
    }

    public function rewind()
    {
       // echo "rewinding\n";
        reset($this->content);
    }
    public function onSend(){
        foreach ($this->content as $cnt){
            $cnt->onSend();
        }
    }
    public function serialize()
    {
        $ret=['Collection'=>[]];
        foreach ($this->content as $cnt){
            $ret['Collection'][] = [$cnt->getModelName()=>$cnt->getContent()];
        }
        if($this->prvUrl !== null) $ret['cursor']['prv']=$this->prvUrl;
        if($this->curUrl !== null) $ret['cursor']['cur']=$this->curUrl;
        if($this->nxtUrl !== null) $ret['cursor']['nxt']=$this->nxtUrl;

        return json_encode($ret,JSON_PRETTY_PRINT);
    }

    public function unserialize($str){}
    public function setNextPageUrl     ($url) {$this->nxtUrl = $url;}
    public function setPreviousPageUrl ($url) {$this->prvUrl = $url;}
    public function setCurrentPageUrl  ($url) {$this->curUrl = $url;}

    private $content;
    private $nxtUrl,$prvUrl,$curUrl;
    private $pageNo,$pageSize;
    private $acceptedClass;
}
