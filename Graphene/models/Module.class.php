<?php
namespace Graphene\models;
use \Exception;
use Graphene\controllers\http\GraphRequest;
use Graphene\controllers\http\GraphResponse;
use Graphene\controllers\Action;
use Graphene\Graphene;

class Module
{

	public function __construct ($modulePath)
	{
		if (! file_exists($modulePath . "/manifest.xml"))
			throw new Exception('module manifest not found for: ',class_name());
		$xml = simplexml_load_file($modulePath . "/manifest.xml");
		$this->namespace = (string) $xml->namespace;
		$this->module_dir = $modulePath;
		$this->author = (string) $xml->info->authorName;
		$this->name = (string) $xml->info->name;
		$this->version = (string) $xml->info->version;
		$this->authEmail = (string) $xml->info->authorEmail;
		$this->support = (string) $xml->info->support;
		$this->domain = $xml->domain;
		$this->beansPath = (string) $xml->beansPath;
		$this->xml = $xml;
		$this->loadFilters($this->xml->filters);
	}

	public function getBeanDirectory ($beanClass)
	{
		return $this->getModuleDir() . '/' . $this->beansPath . '/' . $beanClass .
				 '.php';
	}

	private function loadFilters ($filtersXml)
	{
		// print_r(($filtersXml->filter));
		$framework = Graphene::getInstance();
		if ($filtersXml->filter == null)
			$filters = array();
		else
			$filters = $filtersXml->filter;
		
		foreach ($filters as $filter) {
			if (file_exists($this->module_dir . '/' . $filter->handlerFile)) {
				require_once $this->module_dir . '/' . $filter->handlerFile;
				$filterClass = (string) $filter->handler;
				$filterClass = new $filterClass();
				$filterClass->setUp($this, $filter->scope, $filter->onAction, 
						$filter->onModule);
				$framework->addFilter($filterClass);
			}
		}
	}

	public function exec (GraphRequest $request)
	{
		$this->instantiateActions($this->xml->actions, $request);
		$rUrl = $this->getActionUrl($request);
		foreach ($this->actions as $action) {
			$this->currentAction = $action;
			if ($action->isHandled()) {
				$this->currentAction = $action;
				return $action->start();
			} else
				$this->currentAction = null;
		}
		if(strcasecmp($request->getMethod(),'OPTIONS')==0) return $this->getOptionResponse($request);
		return null;
	}
	private function getOptionResponse(GraphRequest $request){
		$res= new GraphResponse();
		$res->setStatusCode(200);
		$res->setHeader('allow', 'HEAD,GET,POST,PUT,PATCH,DELETE,OPTIONS');
		//var_dump($request);
		//var_dump($request->getHeader('Access-Control-Request-Headers'));
		//$res->setHeader('Access-Control-Allow-Headers', $request->getHeader('Access-Control-Request-Headers'));
		return $res;
	}
	public function getActionUrl (GraphRequest $request)
	{
		return substr($request->getUrl(), strlen((string) $this->domain) + 2);
	}

	public function getCurrentAction ()
	{
		return $this->currentAction;
	}

	public function getAction ($action)
	{
		$this->actions = array();
		foreach ($actions->action as $action) {}
	}

	private function instantiateActions ($actions, $request)
	{
		if ($actions == null)
			return;
		$this->actions = array();
		/* Direttiva autloader caricamento actions namespace\actions\ */
		foreach ($actions->action as $action) {
			if (file_exists($this->module_dir . '/' . $action->handlerFile)) {
				require_once $this->module_dir . '/' . $action->handlerFile;
				$handlerClass = (string) $this->namespace.'\\'.$action->handler;
				$actionClass = new $handlerClass();
				$actionClass->setUp($this, $action, $request);
				$this->actions[] = $actionClass;
			}
		}
	}

	public function getAuthorEmail ()
	{
		return $this->authEmail;
	}

	public function getDomain ()
	{
		return $this->domain;
	}

	public function getName ()
	{
		return $this->name;
	}

	public function getAuthor ()
	{
		return $this->author;
	}

	public function getActions ()
	{
		return $this->actions;
	}

	public function getSupport ()
	{
		return $this->support;
	}

	public function getVersion ()
	{
		return $this->version;
	}

	public function getModuleDir ()
	{
		return $this->module_dir;
	}

	public function getNamespace ()
	{
		return $this->namespace;
	}

	public function isActionsModule ()
	{
		return $this->actions != null;
	}

	public function haveAction ($action)
	{
		$names = $this->getActionNames();
		foreach ($names as $actName) {
			if (strcasecmp($actName, $action) == 0)
				return true;
		}
		return false;
	}

	public function getActionNames ()
	{
		// Array NOME_AZIONE
		$ret = array();
		$actions = $this->xml->actions->action;
		foreach ($actions as $action) {
			$ret[] = strtoupper((string) $this->namespace . '.' . $action->name);
		}
		return $ret;
	}

	private $beansPath;

	private $currentAction;

	private $xml;

	private $request; // richiesta

	protected $actions; // azioni

	protected $namespace; // nome univoco del modulo

	protected $module_dir; // Pathname del modulo

	protected $domain; // Dominio del modulo all'interno dell'api

	protected $version; // Versione del modulo

	protected $name; // Nome esteso del modulo

	protected $author; // Autore o autori del modulo

	protected $authEmail; // email degli autori

	protected $support; // sito o contatto di supporto
}
