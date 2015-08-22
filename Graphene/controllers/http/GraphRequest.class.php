<?php
namespace Graphene\controllers\http;

use Graphene\controllers\Action;
use Graphene\Graphene;

class GraphRequest
{

    public function __construct()
    {
        $this->url = null;
        $this->pars = array();
        $this->headers = array();
    }
    
    // Setters
    public function setMethod($method)
    {
        $this->method = $method;
    }

    public function setBody($body)
    {
        $this->body = $body;
    }

    public function setPars($pars)
    {
        $this->pars = $pars;
    }

    public function setUrl($url)
    {
        $this->url = $url;
    }

    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    public function setUserAgent($agent)
    {
        $this->userAgent = $agent;
    }

    public function setHeader($key, $value)
    {
        $this->headers[$key] = $value;
    }
    // Getters
    public function appendForward(Action $action)
    {
        if (isset($this->headers['forwarded-by']))
            $this->headers['forwarded-by'] = $this->headers['forwarded-by'] . '/' . $action->getUniqueActionName();
        else
            $this->headers['forwarded-by'] = $action->getUniqueActionName();
    }

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
        return $this->body;
    }

    public function getIp()
    {
        return $this->ip;
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
        if (isset($this->pars[$parName])) {
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
        if (! isset($this->headers[$key]))
            return null;
        else
            return $this->headers[$key];
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function setHeaders($headers = null)
    {
        if ($headers === null)
            $this->headers = array();
        $this->headers = $headers;
    }

    private $ip, $method, $url, $pars, $headers, $body, $userAgent;
}