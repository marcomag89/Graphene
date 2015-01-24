<?php

namespace Graphene\models;

use \Exception;
use Graphene\controllers\http\GraphRequest;
use Graphene\controllers\http\GraphResponse;
use Graphene\controllers\Action;
use Graphene\Graphene;

class Module {
	public function __construct($modulePath) {
		if (! file_exists ( $modulePath . "/manifest.xml" )) throw new Exception ( 'module manifest not found for: ', class_name () );
		$xml = json_decode (json_encode(simplexml_load_file ( $modulePath . "/manifest.xml" )),true);
		//print_r($xml);
		$this->module_dir 	= $modulePath;
		$this->version 		= $xml['info']['@attributes']['version'];
		$this->namespace 	= $xml['info']['@attributes']['namespace'];
		$this->name 		= $xml['info']['@attributes']['name'];
			
		//Setting up module domain
		if(isset($xml['info']['@attributes']['domain'])) $this->domain=$xml['info']['@attributes']['domain'];
		else $this->domain=$xml['info']['@attributes']['namespace'];
		
		//Setting up beans path
		if(isset($xm['info']['info']['@attributes']['beans-path'])) $this->beansPath=$xml['info']['@attributes']['beans-path'];
		else $this->beansPath='beans';
		
		$this->author 		= $xml['info']['@attributes']['author'];
		$this->support 		= $xml['info']['@attributes']['support'];
		
		if(isset($xml['filter'])) $this->loadFilters($xml['filter']);
		
		$this->xml=$xml;
	}
	public function getBeanDirectory($beanClass) {
		return $this->getModuleDir () . '/' . $this->beansPath . '/' . $beanClass . '.php';
	}
	private function loadFilters($filtersXml) {
		// print_r(($filtersXml->filter));
		$framework = Graphene::getInstance ();
		//Non array defeat		
		if(isset($filtersXml['@attributes']))$filtersXml=array($filtersXml);
		$filters = $filtersXml;
		foreach ( $filters as $filter ) {
			$expl=explode('@', $filter['@attributes']['handler']);
			$file=$expl[1];
			$class=$expl[0];
			if (file_exists ( $this->module_dir . '/' . $file)) {
				require_once $this->module_dir . '/' . $file;
				$filterClass = $this->namespace.'\\'.$class;
				$filterClass = new $filterClass ();
				$filterClass->setUp ($this, $filter['@attributes']);
				$framework->addFilter ( $filterClass );
			}
		}
	}
	
	public function exec(GraphRequest $request) {
		if(! isset($this->xml['action']))return null;
		$this->instantiateActions ( $this->xml['action'], $request );
		$rUrl = $this->getActionUrl ( $request );
		//print_r($this->actions);
		foreach ( $this->actions as $action ) {
			$this->currentAction = $action;
			if ($action->isHandled ()) {
				$this->currentAction = $action;
				return $action->start ();
			} else
				$this->currentAction = null;
		}
		if (strcasecmp ( $request->getMethod (), 'OPTIONS' ) == 0)
			return $this->getOptionResponse ( $request );
		return null;
	}
	private function getOptionResponse(GraphRequest $request) {
		$res = new GraphResponse ();
		$res->setStatusCode ( 200 );
		$res->setHeader ( 'allow', 'HEAD,GET,POST,PUT,PATCH,DELETE,OPTIONS' );
		// var_dump($request);
		// var_dump($request->getHeader('Access-Control-Request-Headers'));
		// $res->setHeader('Access-Control-Allow-Headers', $request->getHeader('Access-Control-Request-Headers'));
		return $res;
	}
	public function getActionUrl(GraphRequest $request) {
		return substr ( $request->getUrl (), strlen ( ( string ) $this->domain ) + 2 );
	}
	public function getCurrentAction() {
		return $this->currentAction;
	}
	public function getAction($action) {
		$this->actions = array ();
		foreach ( $actions->action as $action ) {
		}
	}
	private function instantiateActions($actions, $request) {
		if(isset($actions['@attributes']))$actions=array($actions);
		$this->actions = array ();
		/* Direttiva autloader caricamento actions namespace\actions\ */
		foreach ( $actions as $action ) {
			if(str_starts_with($action['@attributes']['name'], '$')) $this->injectActions($action,$request);
			else $this->loadAction($action,$request);
		}
	}
	private function loadAction($action,$request,$dir=null,$namespace=null,$pars=null){
		if($dir==null)$dir=$this->getModuleDir();
		if($namespace==null) $namespace=$this->getNamespace();
		if($pars == null && isset($action['@attributes']['handler'])) 
			$pars=explode(',', $action['@attributes']['handler']); 
		else if($pars == null && !isset($action['@attributes']['handler'])) $pars=array();
		
		$expl=explode('@', $action['@attributes']['handler']);
		$file=$expl[1];
		$class=$expl[0];
		//echo $dir. '/' . $file."\n";
		//echo 'check file: '.$dir . '/' . $file."\n";
		if (file_exists ( $dir . '/' . $file )) {
			//echo 'injecting: '.$dir . '/' . $file."\n";
			require_once $dir. '/' . $file;
			$handlerClass = $namespace. '\\' . $class;
			$actionClass = new $handlerClass ();
			$actionClass->setUp ( $this, $action['@attributes'], $request,$pars);
			//echo $actionClass->getUniqueActionName()."\n";
			$this->actions [] = $actionClass;
		}
	}
	private function injectActions($injection,$request){
		$injectionDir=Graphene::getInstance()->getRouter()->getInjectionDir();
		$injectionName= strtoupper(substr($injection['@attributes']['name'], 1));
		if(file_exists($injectionDir.'/'.$injectionName.'/manifest.xml')){
			//echo 'injection is possible for: '.$injectionDir.'/'.$injectionName.'/manifest.xml';
			$injXml=json_decode (json_encode(simplexml_load_file ( $injectionDir.'/'.$injectionName.'/manifest.xml' )),true);
			if(isset($injXml['action']))$actions=$injXml['action'];
			else $actions=array();
			foreach ( $actions as $action ) {
				if(str_starts_with($action['@attributes']['name'], '$')) $this->injectActions($action,$request);
				else{
					if(isset($injection['@attributes']['pars']))$pars=$injection['@attributes']['pars'];
					else $pars=array();
					$this->loadAction($action,$request,$injectionDir.'/'.strtoupper($injectionName),'injection',explode(',', $pars));
				}
			}
		}else {
			echo 'no injection for: '.$injectionDir.'/'.$injectionName.'/manifest.xml';
		}
	}
	public function getAuthorEmail() {
		return $this->authEmail;
	}
	public function getDomain() {
		return $this->domain;
	}
	public function getName() {
		return $this->name;
	}
	public function getAuthor() {
		return $this->author;
	}
	public function getActions() {
		return $this->actions;
	}
	public function getSupport() {
		return $this->support;
	}
	public function getVersion() {
		return $this->version;
	}
	public function getModuleDir() {
		return $this->module_dir;
	}
	public function getNamespace() {
		return $this->namespace;
	}
	public function isActionsModule() {
		return $this->actions != null;
	}
	public function haveAction($action) {
		$names = $this->getActionNames ();
		foreach ( $names as $actName ) {
			if (strcasecmp ( $actName, $action ) == 0)
				return true;
		}
		return false;
	}
	public function getActionNames() {
		$this->instantiateActions($this->xml['action'], new GraphRequest());
		foreach ( $this->actions as $action ) {
			$ret [] = strtoupper ($this->namespace) . '.' . $action->getActionName();
		}
		return $ret;
	}
	private $beansPath;
	private $currentAction;
	private $xml;
	private $request; 		// richiesta
	protected $actions; 	// azioni
	protected $namespace; 	// nome univoco del modulo
	protected $module_dir; 	// Pathname del modulo
	protected $domain; 		// Dominio del modulo all'interno dell'api
	protected $version; 	// Versione del modulo
	protected $name; 		// Nome esteso del modulo
	protected $author; 		// Autore o autori del modulo
	protected $authEmail; 	// email degli autori
	protected $support; 	// sito o contatto di supporto
}
