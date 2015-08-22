<?php
namespace Graphene\models;

use \Exception;
use Graphene\controllers\http\GraphRequest;
use Graphene\controllers\http\GraphResponse;
use Graphene\controllers\Action;
use Graphene\Graphene;

class Module
{

    public function __construct($modulePath)
    {
        $this->module_dir = $modulePath;
        if(!$this->loadJsonManifest($modulePath) && !$this->loadXmlManifest($modulePath))
            throw new Exception('module manifest not found for: '.$modulePath);
    }

    private function loadJsonManifest($modulePath){
        if (! file_exists($modulePath . "/manifest.json")) return false;

        $jsonStr = file_get_contents($modulePath . "/manifest.json");
        $json = json_decode($jsonStr,true);
        $this->version    = $json['info']['version'];
        $this->namespace  = $json['info']['namespace'];
        $this->name       = $json['info']['name'];
        $this->author     = $json['info']['author'];
        $this->support    = $json['info']['support'];

        // Setting up module domain
        if (isset($json['info']['domain']))  $this->domain = $json['info']['domain'];
        else $this->domain = $json['info']['namespace'];

        // Setting up models path
        if (isset($json['info']['models-path'])) $this->modelsPath = $json['info']['models-path'];
        else $this->modelsPath = 'models';

        //Load filters
        if (isset($json['filter'])) $this->loadFilters($json['filter']);

        if (!isset($json['actions'])) $json['actions'] = array();
        $this->manifest = $json;
        return true;
    }

    private function loadXmlManifest($modulePath){
        if (! file_exists($modulePath . "/manifest.xml")) return false;

        $xml = json_decode(json_encode(simplexml_load_file($modulePath . "/manifest.xml")), true);
        $xml['v']    = $xml['@attributes']['v'];
        $xml['info'] = $xml['info']['@attributes'];

        $this->version    = $xml['info']['version'];
        $this->namespace  = $xml['info']['namespace'];
        $this->name       = $xml['info']['name'];
        $this->author     = $xml['info']['author'];
        $this->support    = $xml['info']['support'];

        // Setting up module domain
        if (isset($xml['info']['domain']))  $this->domain = $xml['info']['domain'];
        else $this->domain = $xml['info']['namespace'];

        // Setting up models path
        if (isset($xml['info']['models-path'])) $this->modelsPath = $xml['info']['models-path'];
        else $this->modelsPath = 'models';

        //Load filters
        if (isset($xml['filter'])) $this->loadFilters($xml['filter']);

        if(isset($xml['action'])) $xml['actions'] = $xml['action'];
        else $xml['actions'] = array();

        foreach($xml['actions'] as &$action){
            if(isset ($action['@attributes'])){
                $action = $action['@attributes'];
            }
        }

        unset ($xml['action']);
        unset ($xml['info']['@attributes']);
        unset ($xml['@attributes']);

        $this->manifest = $xml;
        return true;
    }

    public function getModelDirectory($modelClass)
    {
        return $this->getModuleDir() . '/' . $this->modelsPath . '/' . $modelClass . '.php';
    }

    private function loadFilters($filtersXml)
    {
        // print_r(($filtersXml->filter));
        $framework = Graphene::getInstance();
        // Non array defeat
        if (isset($filtersXml['@attributes']))
            $filtersXml = array(
                $filtersXml
            );
        $filters = $filtersXml;
        foreach ($filters as $filter) {
            $expl = explode('@', $filter['@attributes']['handler']);
            $file = $expl[1];
            $class = $expl[0];
            if (file_exists($this->module_dir . '/' . $file)) {
                /** @noinspection PhpIncludeInspection */
                require_once $this->module_dir . '/' . $file;
                $filterClass = $this->namespace . '\\' . $class;
                $filterClass = new $filterClass();
                $filterClass->setUp($this, $filter['@attributes']);
                $framework->addFilter($filterClass);
            }
        }
    }

    public function exec(GraphRequest $request)
    {
        if (! isset($this->manifest['actions'])) return null;
        $this->instantiateActions($this->manifest['actions'], $request);
        $rUrl = $this->getActionUrl($request);

        foreach ($this->actions as $action) {
            $this->currentAction = $action;
            if ($action->isHandled()) {
                $this->currentAction = $action;
                return $action->start();
            } else
                $this->currentAction = null;
        }
        if (strcasecmp($request->getMethod(), 'OPTIONS') == 0)
            return $this->getOptionResponse($request);
        return null;
    }

    private function getOptionResponse(GraphRequest $request)
    {
        $res = new GraphResponse();
        $res->setStatusCode(200);
        $res->setHeader('allow', 'HEAD,GET,POST,PUT,PATCH,DELETE,OPTIONS');
        return $res;
    }

    public function getActionUrl(GraphRequest $request)
    {
        return substr($request->getUrl(), strlen((string) $this->domain) + 2);
    }

    public function getCurrentAction()
    {
        return $this->currentAction;
    }

    public function getAction($action)
    {
        $this->actions = array();
        //foreach ($actions->action as $action) {}
    }

    private function instantiateActions($actions, $request)
    {
        $this->actions = array();
        foreach ($actions as $action) {
            if (str_starts_with($action['name'], '$'))
                $this->injectActions($action, $request);
            else
                $this->loadAction($action, $request);
        }
    }

    private function loadAction($action, $request, $dir = null, $namespace = null, $pars = null, $queryPrefix = '')
    {
        if ($dir == null) $dir = $this->getModuleDir();
        if ($namespace == null) $namespace = $this->getNamespace();
        if ($pars == null && isset($action['handler']))
            $pars = explode(',', $action['handler']);
        else 
            if ($pars == null && ! isset($action['handler']))
                $pars = array();
        
        $expl = explode('@', $action['handler']);
        $file = $expl[1];
        $class = $expl[0];
        if (file_exists($dir . '/' . $file)) {
            require_once $dir . '/' . $file;
            $handlerClass = $namespace . '\\' . $class;
            $actionClass = new $handlerClass();
            $actionClass->setUp($this, $action, $request, $pars, $queryPrefix);
            $this->actions[] = $actionClass;
        }
    }

    private function injectActions($injection, $request)
    {
        $injectionDir = Graphene::getInstance()->getRouter()->getInjectionDir();
        $injectionName = strtoupper(substr($injection['name'], 1));
        if (file_exists($injectionDir . '/' . $injectionName . '/manifest.xml')) {
            $injXml = json_decode(json_encode(simplexml_load_file($injectionDir . '/' . $injectionName . '/manifest.xml')), true);
            if (isset($injXml['action']))
                $actions = $injXml['action'];
            else
                $actions = array();

            foreach ($actions as $action) {
                $action=$action['@attributes'];
                if (str_starts_with($action['name'], '$'))
                    $this->injectActions($action, $request);
                else {
                    if (isset($injection['pars']))
                        $pars = explode(',', $injection['pars']);
                    else
                        $pars = array();
                    if (isset($injection['query-prefix']))
                        $pfx = $injection['query-prefix'];
                    else
                        $pfx = '';
                    $this->loadAction($action, $request, $injectionDir . '/' . strtoupper($injectionName), 'injection', $pars, $pfx);
                }
            }
        } else {
            echo 'no injection for: ' . $injectionDir . '/' . $injectionName . '/manifest.xml';
        }
    }

    public function getAuthorEmail()
    {
        return $this->authEmail;
    }

    public function getDomain()
    {
        return $this->domain;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function getActions()
    {
        return $this->actions;
    }

    public function getSupport()
    {
        return $this->support;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function getModuleDir()
    {
        return $this->module_dir;
    }

    public function getNamespace()
    {
        return $this->namespace;
    }

    public function isActionsModule()
    {
        return $this->actions != null;
    }

    public function haveAction($action)
    {
        $names = $this->getActionNames();
        foreach ($names as $actName) {
            if (strcasecmp($actName, $action) == 0)
                return true;
        }
        return false;
    }

    public function getActionNames()
    {
        $this->instantiateActions($this->manifest['actions'], new GraphRequest());
        foreach ($this->actions as $action) {
            $ret[] = $action->getUniqueActionName();
        }
        return $ret;
    }
    public function getActionDocs()
    {
        $this->instantiateActions($this->manifest['actions'], new GraphRequest());
        foreach ($this->actions as $action) {
          //  print_r($action);
            $ret[] = array(
                "name"   => $action->getUniqueActionName(),
                "url"    => $_SERVER['SERVER_NAME'].'/'.$action->getActionUrl(),
                "method" => $action->getHandlingMethod(),
            );
        }
        return $ret;
    }

    private $modelsPath;

    private $currentAction;

    private $manifest;

    private $request;
 // richiesta
    protected $actions;
 // azioni
    protected $namespace;
 // nome univoco del modulo
    protected $module_dir;
 // Pathname del modulo
    protected $domain;
 // Dominio del modulo all'interno dell'api
    protected $version;
 // Versione del modulo
    protected $name;
 // Nome esteso del modulo
    protected $author;
 // Autore o autori del modulo
    protected $authEmail;
 // email degli autori
    protected $support; // sito o contatto di supporto
}
