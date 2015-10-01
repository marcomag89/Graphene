<?php
namespace Graphene\controllers\exceptions;

use \Exception;

class GraphException extends Exception
{

    public function __construct($message, $code = 500, $httpCode = null)
    {
        parent::__construct($message, $code);
        if($httpCode == null) $httpCode = $code;
        $this->httpCode = $httpCode;
    }

    public function getHttpCode()
    {
        return $this->httpCode;
    }

    protected $httpCode;
}