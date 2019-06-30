<?php

namespace Graphene\models;

class ModelCollection implements \Iterator, \Serializable, \Countable {

    public function __construct(Model $model) {
        $this->acceptedClass = get_class($model);
        $this->content = [];
    }

    public function setPage($page) {
        $this->pageNo = $page;
    }

    public function setPageSize($pageSize) {
        $this->pageSize = $pageSize;
    }

    public function getPage() {
        return $this->pageNo;
    }

    public function getPageSize() {
        return $this->pageSize;
    }

    public function add($model) {
        if (!is_array($model)) {
            $models = [
                $model
            ];
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

    public function remove($index) {
        if (isset($this->content[$index])) {
            array_slice($this->content, $index);
        }
    }

    // Iterator functions
    public function current() {
        $var = current($this->content);

        //echo "current: $var\n";
        return $var;
    }

    public function next() {
        $var = next($this->content);

        //echo "next: $var\n";
        return $var;
    }

    public function key() {
        $var = key($this->content);

        //echo "key: $var\n";
        return $var;
    }

    public function valid() {
        $key = key($this->content);
        $var = ($key !== null && $key !== false);

        return $var;
    }

    public function rewind() {
        // echo "rewinding\n";
        reset($this->content);
    }

    public function onSend() {
        foreach ($this->content as $cnt) {
            $cnt->onSend();
        }
    }

    public function serialize() {
        return json_encode($this->getData(), JSON_PRETTY_PRINT);
    }

    public function getData() {
        $ret = ['Collection' => []];
        foreach ($this->content as $cnt) {
            $ret['Collection'][] = [$cnt->getModelName() => $cnt->getContent()];
        }
        if ($this->prvUrl !== null) {
            $ret['cursor']['prv'] = $this->prvUrl;
        }
        if ($this->curUrl !== null) {
            $ret['cursor']['cur'] = $this->curUrl;
        }
        if ($this->nxtUrl !== null) {
            $ret['cursor']['nxt'] = $this->nxtUrl;
        }

        return $ret;
    }

    /**
     *
     * @return Model|null
     */
    public function getFirst() {
        if (count($this->content) > 0) {
            return $this->content[0];
        } else {
            return null;
        }
    }

    public function unserialize($str) {
    }

    public function setNextPageUrl($url) {
        $this->nxtUrl = $url;
    }

    public function setPreviousPageUrl($url) {
        $this->prvUrl = $url;
    }

    public function setCurrentPageUrl($url) {
        $this->curUrl = $url;
    }

    public function count() {
        return count($this->content);
    }

    private $content;
    private $nxtUrl, $prvUrl, $curUrl;
    private $pageNo, $pageSize;
    private $acceptedClass;

    /**
     * Count elements of an object
     * @link https://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
}
