<?php
namespace Graphene\controllers\exceptions;
use \Exception;

class BeanException extends Exception
{

	public function __construct ($msg, $code)
	{
		parent::__construct($msg, $code);
	}
}