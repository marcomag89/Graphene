<?php
namespace Graphene\controllers;

class UrlProcessor
{

    public function __construct($pattern)
    {
        $pattern = url_trimAndClean($pattern);
        $this->pattern = strtolower($pattern);
        $this->matchedPars = array();
    }

    public function matches($url){
        return $this->checkEmpty($url) || $this->checkUrl($url);
    }

    /* Empty or monopar matcher */
    private function checkEmpty($url){
        if (strcmp($url, '') == 0 && ($this->pattern == null || strcmp($this->pattern, '') == 0))
            return true;
        if (! str_contains($this->pattern, '/') && str_starts_with($this->pattern, ':')) {
            $parName = substr($this->pattern, 1);
            $this->matchedPars[$parName] = $url;
            return true;
        } else {
            return false;
        }
    }
    
    private function checkUrl($url){
        $url = url_trimAndClean($url);
        preg_match_all('/:(\w+)/', $this->pattern, $matches);
        $regex = str_replace('/', '\/', $this->pattern);
        $regex ='/^'.preg_replace('/:(\w+)/', '(\\w+)', $regex).'(\/|)$/';
        $parLabels = $matches[1];
        $parsKV=[];
        
        if(preg_match($regex, $url,$matches)){
            for($i=0; $i<count($parLabels); $i++){$parsKV[$parLabels[$i]] = $matches[$i+1];}
            $this->matchedPars=$parsKV;
            return true;
        }else{
            return false;
        }
    }

    /* Url matcher */
    public function getPars(){
        return $this->matchedPars;
    }

    private $matchedPars;
    private $pattern;
}