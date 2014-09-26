<?php
namespace Graphene\controllers;
use Graphene\models\Module;
use Graphene\controllers\http\GraphRequest;

class Filter
{

	public function setUp ($ownerModule, $scope, $onActions, $onModules)
	{
		if ($scope == null)
			$scope = 'local';
		if ($onActions == null)
			$onActions = array();
		if ($onModules == null)
			$onModules = array();
		$this->actions = array();
		$this->modules = array();
		$this->ownerModule = $ownerModule;
		$this->scope = $scope;
		
		foreach ($onActions as $act)
			$this->actions[] = (string) $act;
		foreach ($onModules as $mod)
			$this->modules[] = (string) $mod;
	}

	public function isHandled (Module $mod, Action $action)
	{
		
		// Gestione Azioni
		if (strcasecmp($this->scope, 'local') == 0) { // Scope locale
			if ($this->actions == null && $this->modules == null && $this->ownerModule->haveAction(
					$action->getUniqueActionName()))
				return true; // tutte le azioni del modulo corrente
			foreach ($this->actions as $hAction) {
				if (strcasecmp($hAction, $action->getUniqueActionName()) && $this->ownerModule->haveAction(
						$action->getUniqueActionName()))
					return true;
			}
		} else {
			if (strcasecmp($this->scope, 'global') == 0) {
				if ($this->actions == null && $this->modules == null)
					return true; // tutte le azioni di tutti i moduli
				foreach ($this->actions as $hAction) {
					if (trcasecmp($hAction, $action->getUniqueActionName()) == 0)
						return true;
				}
			}
		}
		
		return false;
	}

	public function getMessage ()
	{
		return $this->message;
	}

	public function getStatus ()
	{
		return $this->status;
	}

	public function isOk ()
	{
		return $this->status < 400;
	}

	public function exec (GraphRequest $req, Module $mod, Action $action)
	{
		$this->message = self::DEFAULT_MESSAGE;
		$this->status = self::DEFAULT_STATUS;
		$this->request = $req;
		$this->module = $mod;
		$this->action = $action;
		
		if ($this->isHandled($mod, $action)) {
			$this->run();
		}
		return $this->isOk();
	}

	public function getName ()
	{
		return get_called_class();
	}

	public function run ()
	{
		$this->isOk();
	}

	protected $message;

	protected $status;

	protected $request;

	protected $module;

	protected $action;

	protected $scope;

	protected $actions;

	protected $modules;

	protected $ownerModule;

	const DEFAULT_MESSAGE = 'ok';

	const DEFAULT_STATUS = 200;
}