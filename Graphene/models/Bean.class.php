<?php
namespace Graphene\models;
use Graphene\Graphene;
use Graphene\controllers\bean\BeanController;
use Graphene\controllers\bean\BeanFactory;
use \Exception;
use Graphene\controllers\http\GraphResponse;

abstract class Bean
{

	public function __construct ()
	{
		$this->structs = $this->getStructs();
		$this->beanController = new BeanController($this->getCustomCrudDriver(), 
				$this->getStructs(), $this, func_get_args());
	}
	public static function getByResponse(GraphResponse $res){
		$requestBeans = BeanFactory::createByResponse($res);
		if (isset($requestBeans[self::stcName()]))
			return $requestBeans[self::stcName()];
		else
			throw new Exception('Bad response', 400);
	}
	public static function getByRequest ()
	{
		$req = Graphene::getInstance()->getRequest();
		$requestBeans = BeanFactory::createByRequest($req);
		if (isset($requestBeans[self::stcName()]))
			return $requestBeans[self::stcName()];
		else
			throw new Exception('Bad request', 400);
	}
	/*
	 * -----
	 * Getters
	 * -----
	 */
	public static function stcName ()
	{
		return explode('\\', get_called_class())[1];
	}

	public function getName ()
	{
		if ($this->name == null) {
			if (! is_object($this) && ! is_string($this))
				return false;
			$class = explode('\\', 
					(is_string($this) ? $object : get_class($this)));
			$this->name = $class[count($class) - 1];
		}
		return $this->name;
	}

	public function getDomain ()
	{
		if ($this->domain == null) {
			$fw = Graphene::getInstance();
			$this->domain = $fw->getApplicationName() . "." .
					 $fw->getCurrentModule()->getNamespace() . "." .
					 $this->getName();
		}
		return $this->domain;
	}

	public function setLazy ($boolean)
	{
		$this->beanController->setLazy($boolean);
	}

	public function getContent ()
	{
		return $this->content;
	}

	public function getBeanController ()
	{
		return $this->beanController;
	}

	final public function getStruct ()
	{
		return $this->beanController->getStruct();
	}

	final public function isValid ()
	{
		return $this->beanController->checkContent($this);
	}

	public function isEmpty ()
	{
		return count($this->content) == 0;
	}

	public function getLastTestErrors ()
	{
		$errs = $this->beanController->getLastTestErrors();
		$ret = '';
		foreach ($errs as $errField) {
			foreach ($errField as $msm) {
				$ret .= $msm['message'] . ', and ';
			}
		}
		return substr($ret, 0, - 6);
	}
	
	// Serializzation
	public function serialize ()
	{
		return $this->beanController->serialize($this);
	}
	/*
	 * -----
	 * Setters
	 * -----
	 */
	public function setContent ($content)
	{
		$this->content = $content;
	}
	/*
	 * -----------
	 * CRUD Storage
	 * -----------
	 */
	public final function getStorage(){
		return $this->beanController->getStorage();
	}
	
	public final function create ()
	{
		if ($this->canCreate())
			return $this->beanController->create($this);
	}

	public final function read ()
	{
		if ($this->canRead())
			return $this->beanController->read($this);
	}

	public final function update ()
	{
		if ($this->canUpdate())
			return $this->beanController->update($this);
	}

	public final function delete ()
	{
		if ($this->canDelete())
			return $this->beanController->delete($this);
	}

	public final function patch (){
		if ($this->canPatch())
			return $this->beanController->patch($this);
	}
	
	/*
	 * -----------
	 * Dynamic functions
	 * -----------
	 */
	function __call ($funct, $pars){
		return $this->beanController->call($funct, $pars, $this);
	}
	
	/* Extensible functions */
	public abstract function getStructs ();

	public function getCustomCrudDriver (){return null;}
	public function canCreate (){return true;}
	public function canRead (){return true;}
	public function canUpdate (){return true;}
	public function canDelete (){return true;}
	public function canPatch (){return true;}

	private $domain = null;
	private $name = null;
	private $structs;
	private $beanController;
	protected $content = array();
	
/*Tipo di valore*/
	
	const INTEGER 		= '--T_INTEGER';
	const DATE 			= '--T_DATE';
	const DATETIME 		= '--T_DATETIME';	
	const STRING 		= '--T_STRING';
	const BOOLEAN 		= '--T_BOOLEAN';
	const DOUBLE   		= '--T_DOUBLE';
	const ENUM   		= '--T_ENUM:';
	const OBJECT   		= '--T_OBJECT:';
	const COLLECTION   	= '--T_COLLECTION:';

	/* Checkers */	
	const NOT_NULL 		= '--C_NOT_NULL'; 			// vero se il contenuto non e' null
	const NOT_EMPTY 	= '--C_NOT_EMPTY'; 			// vero se il contenuto o l'array non e' vuoto
	const MIN_LENGHT 	= '--C_MIN_LENGHT:'; 		// lunghezza minima del campo
	const MAX_LENGHT 	= '--C_MAX_LENGHT:'; 		// lunghezza massima del campo
	const MAX_VALUE 	= '--C_MAX_VALUE:'; 		// valore massimo del campo ASCII
	const MIN_VALUE 	= '--C_MIN_VALUE:'; 		// valore minimo del campo ASCII	
	const MATCH_PATTERN = '--C_MATCH_PATTERN:'; 	// vero se il campo e compatibile ad un pattern regex
	const ALPHANUMERIC 	= '--C_ALPHANUMERIC:'; 		// vero se alfanumerico	
	const MIN_ELEMENTS 	= '--C_COLL_MIN_ELEMENTS:'; 	// vero solo se la collezione ha almeno il numero di elementi specificati
	const MAX_ELEMS 	= '--C_COLL_MAX_ELEMENTS:'; 	// vero solo se la collezione ha almeno il numero massimo di elementi
}