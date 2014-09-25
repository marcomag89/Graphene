<?php
namespace Graphene\controllers\exceptions;
use \Exception;

class GraphException extends Exception{
	public function __construct($message,$code,$httpCode){
		parent::__construct($message,$code);
		$this->httpCode=$httpCode;
	}
	public function getHttpCode(){
		return $this->httpCode;
	}
	protected $httpCode;
}