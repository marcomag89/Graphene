<?php
namespace Graphene\controllers\exceptions;

use \Exception;

class ModelException extends Exception
{

    public function __construct($msg, $code)
    {
        parent::__construct($msg, $code);
    }
}