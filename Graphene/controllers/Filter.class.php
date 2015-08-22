<?php
namespace Graphene\controllers;

use Graphene\models\Module;
use Graphene\controllers\http\GraphRequest;

class Filter
{

    public function setUp($ownerModule, $attributes)
    {
        if (! isset($attributes['scope']) == null || strcasecmp($attributes['scope'], 'local') == 0)
            $this->$scope = 'local';
        else 
            if (strcasecmp($attributes['scope'], 'global') == 0)
                $this->$scope = 'global';
        
        $this->actions = array();
        $this->modules = array();
        
        if (isset($attributes['actions'])) {
            $this->actions = explode(',', str_replace(' ', '', $attributes['actions']));
        }
        if (isset($attributes['modules'])) {
            $this->actions = explode(',', str_replace(' ', '', $attributes['modules']));
        }
    }

    public function isHandled(Module $mod, Action $action)
    {
        
        // Gestione Azioni
        if (strcasecmp($this->scope, 'local') == 0) { // Scope locale
            if ($this->actions == null && $this->modules == null && $this->ownerModule->haveAction($action->getUniqueActionName()))
                return true; // tutte le azioni del modulo corrente
            foreach ($this->actions as $hAction) {
                if (strcasecmp($hAction, $action->getUniqueActionName()) && $this->ownerModule->haveAction($action->getUniqueActionName()))
                    return true;
            }
        } else {
            if (strcasecmp($this->scope, 'global') == 0) {
                if ($this->actions == null && $this->modules == null)
                    return true; // tutte le azioni di tutti i moduli
                foreach ($this->actions as $hAction) {
                    if (strcasecmp($hAction, $action->getUniqueActionName()) == 0)
                        return true;
                }
            }
        }
        
        return false;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function isOk()
    {
        return $this->status < 400;
    }

    public function exec(GraphRequest $req, Module $mod, Action $action)
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

    public function getName()
    {
        return get_called_class();
    }

    public function run()
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