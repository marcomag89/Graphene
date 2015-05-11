<?php
namespace Graphene\models;

class ModelCollection implements \Iterator, \Serializable
{

    public function __construct(Model $model)
    {
        $this->acceptedClass = get_class($model);
        $this->content = [];
    }

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
    public function current()
    {
        $var = current($this->content);
        echo "current: $var\n";
        return $var;
    }

    public function next()
    {
        $var = next($this->content);
        echo "next: $var\n";
        return $var;
    }

    public function key()
    {
        $var = key($this->content);
        echo "key: $var\n";
        return $var;
    }

    public function valid()
    {
        $key = key($this->content);
        $var = ($key !== NULL && $key !== FALSE);
        echo "valid: $var\n";
        return $var;
    }

    public function rewind()
    {
        echo "rewinding\n";
        reset($this->content);
    }
    public function onSend(){
        foreach ($this->content as $cnt){
            $cnt->onSend();
        }
    }
    public function serialize()
    {
        $ret='{"Collection":[';
        foreach ($this->content as $cnt){
            $ret=$ret."\n    ".$cnt->serialize().",";
        }
        $ret=rtrim($ret, ",");
        $ret=$ret."\n]\n}";
        $ret=json_decode($ret);
        return json_encode($ret,JSON_PRETTY_PRINT);
    }
    public function serializedSend(){
        foreach ($this->content as $cnt){
        
        }
    }
    public function unserialize($serialized)
    {}

    private $content;

    private $acceptedClass;
}
