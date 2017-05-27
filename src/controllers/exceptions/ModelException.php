<?php
namespace Graphene\controllers\exceptions;


class ModelException extends GraphException
{

    public function __construct($msg, $code)
    {
        parent::__construct($msg, $code);
    }
}