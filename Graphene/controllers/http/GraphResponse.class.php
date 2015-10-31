<?php
namespace Graphene\controllers\http;

use Graphene\Graphene;
use Graphene\models\Model;

class GraphResponse
{

    public function __construct($forwarding = false)
    {
        $this->forwarding = $forwarding;
        $this->code = 200;
        $this->headers = array();
        $this->media   = null;
    }
    // Setters
    public function setStatusCode($status)
    {
        $this->code = $status;
    }

    public function setMedia($mediaFile, $mType=null){
        if($mType === null)$mType = str_replace('_','/',explode('|',basename($mediaFile))[0]);
        $this->setHeader('Content-Type',$mType);
        $this->setHeader("Content-Length",filesize($mediaFile));
        $this->media = $mediaFile;
    }

    public function setData($data){
        $this->data=$data;
    }

    public function setBody($body)
    {
        $this->data = json_decode($body,true);
    }

    public function setHeader($key, $value)
    {
        $this->headers[$key] = $value;
    }
    
    // Getters
    public function isForwarding()
    {
        return $this->forwarding;
    }

    public function getStatusCode()
    {
        return $this->code;
    }

    public function getBody()
    {
        return json_encode($this->data,true);
    }

    public function getData(){
        return $this->data;
    }

    public function getMedia(){
        return $this->media;
    }

    public function getHeader($key)
    {
        foreach ($this->headers as $hk => $hv) {
            if (strcasecmp($key, $hk) == 0)
                return $hv;
        }
        return null;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    private $forwarding;

    private $code, $headers, $data, $media;
}