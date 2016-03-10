<?php
namespace Graphene\controllers\http;

use Graphene\controllers\Action;
use Graphene\Graphene;

class GraphRequest
{

    private $ip, $method, $url, $pars, $headers, $data, $userAgent, $contextPars;

    public function __construct()
    {
        $this->url     = null;
        $this->pars    = [];
        $this->headers = [];
        $this->data    = [];
        $this->contextPars = [];
    }

    public function setData($data){
        $this->data=$data;
    }

    // Setters

    public function getData(){
        return $this->data;
    }

    public function setMethod($method)
    {
        $this->method = $method;
    }

    public function setBody($body){
        $this->data = json_decode($body,true);
    }

    public function setPars($pars)
    {
        foreach($pars as $park=>$parv){
            $this->pars[strtolower($park)] = $parv;
        }
    }

    public function setUrl($url)
    {
        $this->url = $url;
    }

    public function setUserAgent($agent)
    {
        $this->userAgent = $agent;
    }

    public function appendForward(Action $action)
    {
        if (isset($this->headers['forwarded-by']))
            $this->headers['forwarded-by'] = $this->headers['forwarded-by'] . '/' . $action->getUniqueActionName();
        else
            $this->headers['forwarded-by'] = $action->getUniqueActionName();
    }

    // Getters

    public function isForwarding()
    {
        return isset($this->headers['forwarded-by']);
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getBody()
    {
        return json_encode($this->data,JSON_PRETTY_PRINT);
    }

    public function getIp()
    {
        return $this->ip;
    }

    public function setIp($ip) {
        $this->ip = $ip;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getHost()
    {
        $url = $this->url;
        $url = str_replace('http://', '', $url);
        return explode('/', $url)[0];
    }

    public function getProtocol()
    {
        if       (str_starts_with($this->url, 'http//'))  return 'http';
        elseif   (str_starts_with($this->url, 'https//')) return 'https';
        else     return 'graphene';
    }

    public function getPathname()
    {
        $url = $this->url;
        $url = str_replace('http://', '', $url);
        $exploded = explode('/', $url);
        unset($exploded[0]);
        $ret = '';
        foreach ($exploded as $e) {
            $ret = $ret . $e . '/';
        }
        if (str_ends_with($ret, '/'))
            $ret = substr($ret, 0, - 1);
        return $ret;
    }

    public function getPar($parName)
    {
        $parName = strtolower($parName);
        if (array_key_exists($parName,$this->pars)) {
            return $this->pars[$parName];
        } else return null;
    }

    public function getPars()
    {
        return $this->pars;
    }

    public function getUserAgent()
    {
        return $this->getHeader('User-Agent');
    }

    public function getHeader($key)
    {
        if(!array_key_exists(strtolower($key),$this->headers))return null;
        else{return $this->headers[strtolower($key)];}
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function setHeaders($headers = null){
        if($headers !== null){
            $this->headers=[];
            foreach ($headers as $hk=>$hv){
                $this->setHeader($hk,$hv);
            }
        }
    }

    public function setHeader($key, $value) {
        if ($key !== null && $value !== null) {
            $this->headers[strtolower($key)] = $value;
        }
    }

    public function setContextPar($key,$value){
        $this->contextPars[$key]=$value;
    }

    public function getContextPar($key){
        if(array_key_exists($key,$this->contextPars))return $this->contextPars[$key];
        else return null;
    }

    public function getContextPars() {
        return $this->contextPars;
    }
}