<?php
namespace Graphene\controllers;

use Graphene\models\Module;
use Graphene\controllers\http\GraphRequest;

class Filter
{

    public function setUp($ownerModule, $settings)
    {
        $this->scope       = $settings['scope'];
        $this->ownerModule = $ownerModule;
        $this->settings= $settings;
    }

    public function isHandled(Module $mod, Action $action)
    {
        if ($this->scope === 'MODULE' && $this->ownerModule->haveAction($action->getUniqueActionName())) { // Scope locale
            return true; // tutte le azioni del modulo corrente
        } else {
            if ($this->scope === 'GLOBAL')  return true;
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
        $this->status  = self::DEFAULT_STATUS;
        $this->request = $req;
        $this->module  = $mod;
        $this->action  = $action;
        
        if ($this->isHandled($mod, $action)) { $this->run(); }
        return $this->isOk();
    }

    public function getName()
    {
        return get_called_class();
    }

    public function run(){}

    /**
     * @var GraphRequest
     */
    protected $request;

    /**
     * @var Module
     */
    protected $module;

    /**
     * @var Action
     */
    protected $action;

    protected $message;
    protected $status;
    protected $scope;
    protected $actions;
    protected $modules;
    protected $ownerModule;
    protected $settings;

    const DEFAULT_MESSAGE = 'ok';
    const DEFAULT_STATUS  = 200;
}