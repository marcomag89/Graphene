<?php

namespace Graphene\controllers;

use Graphene\controllers\http\GraphRequest;
use Graphene\controllers\http\GraphResponse;

use Graphene\models\Module;
use Graphene\Graphene;

/**
 * Router di Graphene:
 * Questa classe cattura l'url di una richiesta restful e lo compara con
 * quelle della propria tabella di routing.
 * Questa operazione si divide in 5 fasi:
 * - Indicizzazione dei Moduli
 * - Creazione e salvataggio della tabella di routing
 * - Matching dell' indirizzo richiesto
 * - Instanziazione del servizio richiesto
 * - Dispatching della richiesta al servizio
 *
 * @author Marco Magnetti <marcomagnetti@gmail.com>
 *        
 */
class GrapheneRouter
{

	public function __construct ()
	{
		$r = Graphene::getInstance();
		$this->modulesDir = $r->getSettings()->moduleurl;
		$this->baseUrl=(string)$r->getSettings()->frameworkDir.'Graphene';
		$this->nativePath=$this->baseUrl.'/native';
		$this->routeTable = array();
		$this->loadModules();
		$this->modStack = array();
	}

	/**
	 * Dirotta la richiesta al servizio che corrisponde al matching, ritornando
	 * una risposta
	 * 
	 * @return GraphResponse
	 */
	public function dispatch (GraphRequest $request)
	{
		$response = null;
		$url = url_trimAndClean($request->getUrl());
		foreach ($this->modules as $dir => $module) {
			$domain = (string) $module->getDomain();
			if (str_starts_with($url, $domain)) {
				$this->pushModule($module);
				$response = $module->exec($request);
				$this->popModule();
				if ($response != null)
					break;
			}
		}
		return $this->getSafeResponse($response);
	}
	/**
	 * Carica i moduli presenti nella cartella preimpostata
	 * 
	 * @return void
	 */
	private function loadModules ()
	{
		try{$mods = scandir($this->modulesDir);}
		catch(\Exception $e){$mods=array();}
		$this->modules = array();
		foreach ($mods as $key => $moduleDir) {
			if (is_dir($this->modulesDir . "/" . $moduleDir) && !str_starts_with($moduleDir, '.')) {
				$module = new Module($this->modulesDir . "/" . $moduleDir);
				if ($module != null) {
					$this->modules[$moduleDir] = $module;
				}
			}
		}
		$sysMods = scandir($this->nativePath);
		foreach ($sysMods as $key => $moduleDir) {
			if (is_dir($this->nativePath.'/'. $moduleDir) && !str_starts_with($moduleDir, '.')) {
						$module = new Module($this->nativePath . "/" . $moduleDir);
						if ($module != null) {
							$this->modules[$moduleDir] = $module;
						}
			}
		}
	}

	/**
	 * Crea una risposta sicura (Modulo non trovato se non e pervenuta una
	 * risposta)
	 * 
	 * @return GraphResponse
	 */
	private function getSafeResponse ($response)
	{
		$filterManager = Graphene::getInstance()->getFilterManager();
		
		if ($response == null) {
			$response = new GraphResponse();
			$response->setHeader('content-type', 'application/json');
			if ($filterManager->haveErrors()) {
				$response->setBody($filterManager->serializeErrors());
				$ff = $filterManager->getFailedFilter();
				$response->setBody(
						json_encode(
								array(
									"error" => array(
										"message" => '[' . $ff['name'] . '] ' .
												 $ff['message'],
												"code" => $ff['status']
									)
								), JSON_PRETTY_PRINT));
				$response->setStatusCode($ff['status']);
			} else {
				$response->setBody(
						json_encode(
								array(
									"error" => array(
										"message" => "action not found",
										"code" => "400"
									)
								), JSON_PRETTY_PRINT));
				$response->setStatusCode(400);
			}
		}
		return $response;
	}

	public function getModuleByActionName ($actionName)
	{
		$modules = $this->getInstalledModules();
		foreach ($modules as $mod) {
			// TODO select for actionName
		}
	}

	public function getModuleByNamespace ($namespace){
		$modules = $this->getInstalledModules();
		foreach ($modules as $mod) {
			if (strcasecmp($mod->getNamespace(), $namespace) == 0)
				return $mod;
		}
		return null;
	}

	private function pushModule ($module)
	{
		array_push($this->modStack, $module);
	}

	private function popModule ()
	{
		$pop = array_pop($this->modStack);
	}

	public function getCurrentModule ()
	{
		$current = end($this->modStack);
		return $current;
	}

	public function getStackModuleNames ()
	{
		$ret = '';
		foreach ($this->modStack as $mod) {
			$ret = $ret . '/' . $mod->getNamespace();
		}
		return $ret;
	}

	public function getModuleStackLevel ()
	{
		return count($this->modStack);
	}

	public function getModuleStack ()
	{
		return $this->modStack;
	}

	public function getInstalledModules ()
	{
		$ret = array();
		foreach ($this->modules as $md => $mod) {
			$ret[] = $mod;
		}
		return $ret;
	}

	private $modules;

	private $baseUrl;
	private $modStack;
	private $nativePath;
	private $request;

	private $modulesDir;
}