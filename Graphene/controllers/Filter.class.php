<?php
namespace Graphene\controllers;

use Graphene\controllers\http\GraphResponse;
use Graphene\Graphene;
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

    /**
     * @param $url
     * @param null $body
     * @param null $method
     * @return GraphResponse
     */
    protected function forward($url, $body = null, $method = null)
    {
        $req = new GraphRequest(true);
        $req->setUrl($url);
        /* Creazione metodo */
        if ($body == null && $method == null)
            $req->setMethod('GET');
        else
            if ($body != null && $method == null)
                $req->setMethod('POST');
            else
                if ($method != null)
                    $req->setMethod($method);
        /* Creazione body */
        if ($body != null)
            $req->setBody($body);
        else
            $req->setBody('');
        $headers = $this->request->getHeaders();
        foreach ($headers as $hk => $hv) {
            $req->setHeader($hk, $hv);
        }
        $req->setUserAgent($this->request->getUserAgent());
        $req->setHeader('forwarded-by', $this->getName());
        //$req->appendForward($this);
        /* Forwarding */
        $fw = Graphene::getInstance();
        $req->setHeader('system-token',Graphene::getInstance()->getSystemToken());
        return $fw->forward($req);
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
    public function getSettings(){
        return $this->settings;
    }

    public function getName()
    {
        return $this->settings['unique-name'];
    }
    public function getModuleName(){
        return $this->ownerModule->getName();
    }
    public function getAfter(){
        return $this->settings['after'];
    }

    public function getBefore(){
        return $this->settings['before'];
    }
    /**
     * @return Module
     */
    public function getOwnerModule(){
        return $this->ownerModule;
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

    /**
     * @var Module
     */
    protected $ownerModule;
    protected $settings;

    const DEFAULT_MESSAGE = 'ok';
    const DEFAULT_STATUS  = 200;
}