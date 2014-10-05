<?php
namespace Graphene\controllers\bean;
use Graphene\models\Bean;
use Graphene\Graphene;
use Graphene\db\CrudDriver;
use Graphene\db\CrudStorage;
use Graphene\controllers\bean\BeanChecker;
use Graphene\controllers\exceptions\GraphException;


class BeanController
{
	public function __construct ($crudDriver, $structs, $args)
	{
		$this->structs = $structs;
		$this->args = $args;
		$this->corrupt = false;
		$this->beanChecker = new BeanChecker();
		$this->createBasicStruct();
		// Controlla se e stato settato un driver personalizzato
		if ($crudDriver != null)
			$this->setCrudDriver($crudDriver);
		else
			$this->storage = Graphene::getInstance()->getStorage();
			// Controlla gli argomenti passati da API
		if ($args == null)
			$this->emptyInit();
		else
			foreach ($this->args as $arg) {
				if ($arg instanceof GraphRequest)
					$this->requestInit($request);
				else 
					if ($arg instanceof Bean)
						$this->beanInit($bean);
					else 
						if ($arg instanceof String)
							$this->parInit($par);
			}
	}
	/* Initialization */
	private function emptyInit ()
	{}

	private function requestInit (GraphRequest $request)
	{}

	private function parInit (String $par)
	{
		$this->settings[$par] = true;
	}

	private function createBasicStruct ()
	{
		$this->structs[self::BASIC_STRUCT] = array(
			'id' 		=> Bean::STRING . Bean::NOT_EMPTY,
			'version' 	=> Bean::INTEGER . Bean::NOT_EMPTY
		);
	}
	/*
	 * --------
	 * Struct management
	 * --------
	 */
	public function getStruct (){
		
		log_write(self::LOG_NAME . 'getting struct for: '.$this->getCurrentAction()->getActionName());
		$struct = array();
		$action = $this->getCurrentAction()->getActionName();
		if (isset($this->structs[self::BASIC_STRUCT]))
			$struct = $this->structs[self::BASIC_STRUCT];
		if (isset($this->structs[self::LAZY_STRUCT]))
			$struct = array_merge_recursive($struct, 
					$this->structs[self::LAZY_STRUCT]);
		if ($this->getSetting(self::FLAG_LAZY) || ! isset($this->structs[$action]))
			$retstr=$struct;
		else
			$retstr=array_replace_recursive($struct, $actionStruct = $this->structs[$action]);
		if(!$this->beanChecker->checkValidStruct($retstr)) throw new GraphException('Invalid bean struct', 500, 500);
		return $retstr;
	}
	/*
	 * --------
	 * Getters and setters
	 * --------
	 */
	public function call ($funct, $pars, Bean $bean)
	{
		log_write(self::LOG_NAME . 'called dyFunct: ' . $funct);
		$splitted=explode('_',substr($funct, 3));
		$splitted[0]=lcfirst($splitted[0]);
		if (str_starts_with($funct, 'get')) return $this->serveGet($splitted, $bean);
		else if (str_starts_with($funct, 'set')) return $this->serveSet($splitted, $pars[0], $bean);
	}
	
	/* Auto Generated getters */
	public function serveGet ($funct, Bean $bean){
		log_write(self::LOG_NAME . 'Serving get on ' .strToLower(implode('.', $funct)));
		$content = $bean->getContent();
		$struct = $this->getStruct();
			
		$tmps = &$struct;
		$data = $content;
		$temp = &$data;
		foreach ($funct as $k) {
			$tmps = &$tmps[$k];
			$temp = &$temp[$k];
		}
		if (isset($tmps)) {return $temp;} 
		else return null;
	}
	/* Auto Generated setters */
	public function serveSet ($funct, $par, Bean $bean){
		$content = $bean->getContent();
		$struct = $this->getStruct();
		
		$tmps = &$struct;
		$data = &$content;
		$temp = &$data;		
		foreach ($funct as $k) {
			$tmps = &$tmps[$k];
			$temp = &$temp[$k];
		}
		$this->beanChecker->newTest();
		if ((isset($tmps) && ! is_array($tmps) && $this->beanChecker->isValidValue($par, $tmps, implode('_', $funct)))) {
			$temp = $par;
			$bean->setContent($data);
			return true;
		} else {
			$this->corrupt = true;
			return false;
		}
	}

	public function isCorrupt ()
	{
		return $this->corrupt;
	}

	public function getCurrentAction ()
	{
		$action = Graphene::getInstance()->getCurrentModule()->getCurrentAction();
		// log_write(self::LOG_NAME.'Sending current action
		// ('.$action->getActionName().')');
		return $action;
	}
	/*
	 * --------
	 * Storage management
	 * --------
	 */
	public function setCrudDriver (CrudDriver $driver)
	{
		$this->storage = new CrudStorage($driver);
	}

	public function getCrudDriver ()
	{
		return $this->storage->getDriver();
	}
	public function getStorage(){
		return $this->storage;
	}
	private function getSetting ($setting)
	{
		if (isset($this->settings[$setting]))
			return true;
		else
			return false;
	}
	/*
	 * --------
	 * Serializzation
	 * --------
	 */
	public function serialize (Bean $bean)
	{
		$ret = array(
			$bean->getName() => $bean->getContent()
		);
		return json_encode($ret);
	}
	/*
	 * CRUD-P
	 * Create Read Update Delete and Patch routines
	 */
	public function create ($bean)
	{
		return $this->storage->create($bean);
	}

	public function read ($bean)
	{
		return $this->storage->read($bean);
	}

	public function update ($bean)
	{
		return $this->storage->update($bean);
	}

	public function delete ($bean)
	{
		return $this->storage->delete($bean);
	}

	public function patch ($bean)
	{
		return $this->storage->patch($bean);
	}
	/*
	 * --------
	 * Utilities
	 * --------
	 */
	public function setLazy ($boolean)
	{
		$this->settings[self::FLAG_LAZY] = $boolean;
	}
	/*
	 * --------
	 * Struct and content checking
	 * --------
	 */
	public function checkContent(Bean $bean){
		return $this->beanChecker->checkContent($bean, $this->getStruct());
	}

	public function haveErrors ()
	{
		$errs = $this->beanChecker->getLastTestErrors();
		if(count($errs)>0)return true;
	}

	public function getLastTestErrors (){
		if($this->exceeded!=null)return 'unexpected '.$this->exceeded. ' field';
		else $ret=$this->beanChecker->getLastTestErrors();
		return $ret;
	}
	/**
	 * 
	 * @var BeanChecker
	 */
	private $beanChecker;
	private $corrupt;
	private $ready;
	private $args;
	private $settings = array();
	private $storage;
	private $exceeded=null;
	private $structs; // struttura in base all'azione _DEFAULT e la struttura di
	                  // default
	
	/* Costanti */
	const BASIC_STRUCT = '_basic';
	const LAZY_STRUCT = '_lazy';
	const LOG_NAME = '[Bean Controller] ';
	const FLAG_LAZY = '-lzm';
}