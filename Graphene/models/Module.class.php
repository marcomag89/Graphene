<?php
namespace Graphene\models;

use \Exception;
use Graphene\controllers\http\GraphRequest;
use Graphene\controllers\http\GraphResponse;
use Graphene\controllers\ModuleManifest;
use Graphene\Graphene;
use \Log;

class Module
{

    public function __construct($modulePath)
    {
        $this->module_dir = $modulePath;
        $this->manifestManager = new ModuleManifest();
        try{
            $this->manifestManager->read($modulePath);
            $this->manifest = $this->manifestManager->getManifest();

            $this->version    = $this->manifest['info']['version'];
            $this->namespace  = $this->manifest['info']['namespace'];
            $this->name       = $this->manifest['info']['name'];
            $this->author     = $this->manifest['info']['author'];
            $this->domain     = $this->manifest['info']['domain'];

            //Load filters
            $this->loadFilters($this->manifest['filters']);
        }catch (Exception $e){
            Log::err($e->getMessage());
        }
    }

    public function getModelDirectory($modelClass)
    {
        return $this->getModuleDir() . '/' . $this->manifest['info']['models-path'] . '/' . $modelClass . '.php';
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
        Log::debug('searching for action in: '.$this->getName());
        Log::debug(json_encode($this->manifest,JSON_PRETTY_PRINT));
        $this->instantiateActions($this->manifest['actions'], $request);

        foreach ($this->actions as $action) {
            $this->currentAction = $action;
            if ($action->isHandled()) {
                Log::debug($action->getUniqueActionName().' is handled');
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
    }

    private function instantiateActions($actions, $request)
    {
        $this->actions = array();
        foreach ($actions as $action) {
            $this->loadAction($action, $request);
        }
    }

    private function loadAction($action, $request)
    {
        Log::debug('loading action \''.$action['name']);
        if($action['imported']==='true')$namespace = 'imports';
        else $namespace = $this->getNamespace();

        $file        = $action['file'];
        $class       = $action['class'];

        if (file_exists($file)) {
            /** @noinspection PhpIncludeInspection */
            require_once $file;
            $handlerClass =  $namespace . '\\' . $class;
            $actionClass  =  new $handlerClass();
            $actionClass  -> setUp($this, $action, $request);
            $this->actions[] = $actionClass;
            Log::debug('loading action \''.$action['name'].'\' Completed');
        }else{
            Log::err('loading action \''.$action['name'].'\' Fails, file '.$file.' not found');
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
            //echo 'no injection for: ' . $injectionDir . '/' . $injectionName . '/manifest.xml';
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
        return $this->manifest['info']['author'];
    }

    public function getActions()
    {
        return $this->actions;
    }

    public function getSupport()
    {
        return $this->manifest['info']['support'];
    }

    public function getVersion()
    {
        return $this->manifest['info']['version'];
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
