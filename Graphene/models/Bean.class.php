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

	final public function getStruct ($prettyPrint=false)
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
	public function getStorage(){
		return $this->beanController->getStorage();
	}
	
	public function create ()
	{
		if ($this->canCreate())
			return $this->beanController->create($this);
	}

	public function read ()
	{
		if ($this->canRead())
			return $this->beanController->read($this);
	}

	public function update ()
	{
		if ($this->canUpdate())
			return $this->beanController->update($this);
	}

	public function delete ()
	{
		if ($this->canDelete())
			return $this->beanController->delete($this);
	}

	public function patch ()
	{
		if ($this->canPatch())
			return $this->beanController->patch($this);
	}
	
	/*
	 * -----------
	 * Dynamic functions
	 * -----------
	 */
	function __call ($funct, $pars)
	{
		return $this->beanController->call($funct, $pars, $this);
	}
	
	/* Extensible functions */
	public abstract function getStructs ();

	public function getCustomCrudDriver ()
	{
		return null;
	}

	public function canCreate ()
	{
		return true;
	}

	public function canRead ()
	{
		return true;
	}

	public function canUpdate ()
	{
		return true;
	}

	public function canDelete ()
	{
		return true;
	}

	public function canPatch ()
	{
		return true;
	}

	private $domain = null;

	private $name = null;

	private $structs;

	private $beanController;

	protected $content = array();
	
	// COSTANTI TIPO
	// value types
	/** Check integer value */
	const INTEGER_VALUE      = '-int';
	
	/** Date field checker (format 'yy-mm-dd') */
	const DATE_VALUE         = '-dt';
	
	/** String field checker */
	const STRING_VALUE       = '-str';
	
	/** boolean field checker */
	const BOOLEAN_VALUE      = '-bool';
	
	/** float field checker */
	const FLOAT_VALUE        = '-f';
	
	/** double field checker */
	const DOUBLE_VALUE       = '-d';
	
	/** long field checker */
	const LONG_VALUE         = '-l';
	
	/** Alphanumeric field checker 0-9 A-Z */
	const ALPHANUMERIC_VALUE = '-alpha';
	
	/** Uid field checker 0-9 A-Z */
	const UID_VALUE = '-uid:';

	/** Enum field checker
	 * 	
	 * @param list of enum values with commas
	 * @example Bean::ENUM_VALUE.'foo,bar,cont'
	 * 
	 *  */
	const ENUM_VALUE = '-enum:'; // controlla se e' uno dei valori dell'
	                             // argomento
	                             
	// Options
	const NOT_NULL = '-nn'; // vero se il contenuto non e' null

	const NOT_EMPTY = '-ne'; // vero se il contenuto o l'array non e' vuoto

	const MIN_LENGHT = '-minl:'; // lunghezza minima del campo

	const MAX_LENGHT = '-maxl:'; // lunghezza massima del campo
	                             
	// Node clauses
	const NODE = '-nod'; // vero se si tratta di un nodo

	const NUMERIC_KEYS = '-numk'; // vero solo se il nodo e composto da chiavi
	                              // numeriche
	
	const STRING_KEYS = '-strk'; // vero solo se il nodo e composto da chiavi
	                             // stringa
	
	const MIN_ELEMS = '-mine:'; // vero solo se l'array ha almeno il numero di
	                            // elementi specificati
	
	const MAX_ELEMS = '-maxe:'; // vero solo se l'array ha almeno il numero
	                            // massimo di elementi
	
	const ENUM_KEYS = "-enk:"; // Vero solo se gli elementi dell' array sono
		                           // compresi nell' enum definito
}