<?php

namespace Graphene\controllers;

use Graphene\utils\Paths;
use Graphene\utils\Strings;

class UrlProcessor
{

    public function __construct($pattern)
    {
        $pattern = Paths::urlTrimAndClean($pattern);
        $this->pattern = strtolower($pattern);
        $this->matchedPars = array();
    }

    public function matches($url)
    {
        return $this->checkEmpty($url) || $this->checkUrl($url);
    }

    /* Empty or monopar matcher */
    private function checkEmpty($url)
    {
        if (strcmp($url, '') == 0 && ($this->pattern == null || strcmp($this->pattern, '') == 0))
            return true;
        if (!Strings::contains($this->pattern, '/') && Strings::startsWith($this->pattern, ':')) {
            $parName = substr($this->pattern, 1);
            $this->matchedPars[$parName] = $url;
            return true;
        } else {
            return false;
        }
    }

    private function checkUrl($url)
    {
        $url = Paths::urlTrimAndClean($url);
        preg_match_all('/:(\w+)/', $this->pattern, $matches);
        $regex = str_replace('/', '\/', $this->pattern);
        $regex = '/^' . preg_replace('/:(\w+)/', '(\\w+)', $regex) . '(\/|)$/';
        $parLabels = $matches[1];
        $parsKV = [];

        if (preg_match($regex, $url, $matches)) {
            for ($i = 0; $i < count($parLabels); $i++) {
                $parsKV[$parLabels[$i]] = $matches[$i + 1];
            }
            $this->matchedPars = $parsKV;
            return true;
        } else {
            return false;
        }
    }

    /* Url matcher */
    public function getPars()
    {
        return $this->matchedPars;
    }

    private $matchedPars;
    private $pattern;
}