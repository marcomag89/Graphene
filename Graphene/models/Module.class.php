<?php
namespace Graphene\models;

use \Exception;
use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;
use Graphene\controllers\http\GraphRequest;
use Graphene\controllers\http\GraphResponse;
use Graphene\Graphene;
use \Log;

class Module
{

    public function __construct($modulePath)
    {
        $this->module_dir = $modulePath;
        try{
            $this->manifest = $this->loadManifest($modulePath);

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

    private function loadManifest($modulePath){
        $manifest = array();
        $rManifest = $this->loadJson($modulePath);
        if($rManifest === null){ $rManifest = $this->loadXml($modulePath); }

        //Exceptions
        if($rManifest === null)                                throw new GraphException('module manifest not valid in: '.$modulePath,     500);
        if(!array_key_exists('info',      $rManifest        )) throw new GraphException('module info node not found in: '.$modulePath,    500);
        if(!array_key_exists('namespace', $rManifest['info'])) throw new GraphException('module namespace is undefined in: '.$modulePath, 500);
        if(!array_key_exists('name',      $rManifest['info'])) throw new GraphException('module name is undefined in: '.$modulePath,      500);
        if(!array_key_exists('version',   $rManifest['info'])) throw new GraphException('module version is undefined in: '.$modulePath,   500);

        //Defaults
        if(!array_key_exists('support',      $rManifest['info'])) $rManifest['info']['support']      = Graphene::host().'/doc/'.$rManifest['info']['namespace'];
        if(!array_key_exists('domain',       $rManifest['info'])) $rManifest['info']['domain']       = $rManifest['info']['namespace'];
        if(!array_key_exists('models-path',  $rManifest['info'])) $rManifest['info']['models-path']  = 'models';
        if(!array_key_exists('actions-path', $rManifest['info'])) $rManifest['info']['actions-path'] = 'actions';
        if(!array_key_exists('actions',      $rManifest        )) $rManifest['actions']              = array();
        if(!array_key_exists('filters',      $rManifest        )) $rManifest['filters']              = array();


        $manifest['info']    = array();
        $manifest['actions'] = array();
        $manifest['filters'] = array();

        //Informations
        $manifest['info']['version']        = $rManifest['info']['version'];
        $manifest['info']['name']           = $rManifest['info']['name'];
        $manifest['info']['namespace']      = $rManifest['info']['namespace'];
        $manifest['info']['support']        = $rManifest['info']['support'];
        $manifest['info']['domain']         = $rManifest['info']['domain'];
        $manifest['info']['models-path']    = $rManifest['info']['models-path'];
        $manifest['info']['actions-path']   = $rManifest['info']['actions-path'];
        $manifest['info']['author']         = $rManifest['info']['author'];

        //Actions
        foreach($rManifest['actions'] as $k=>$action){
            if(array_key_exists('name', $action)){
                $rManifest['actions'][$k]['name'] = strtoupper($rManifest['actions'][$k]['name']);
                if(!array_key_exists('handler', $action)){
                    $rManifest['actions'][$k]['handler'] = $this->actionNameToCamel($action['name']).'@'.$rManifest['info']['actions-path'].'/'.$rManifest['info']['namespace'].'.'.$action['name'].'.php';
                }
                if(!array_key_exists('method', $action)) $rManifest['actions'][$k]['method']='get';
                if(!array_key_exists('query', $action))  $rManifest['actions'][$k]['query']='';

                $manifest['actions'][$k]=array();
                $manifest['actions'][$k]['name']    = $rManifest['actions'][$k]['name'];
                $manifest['actions'][$k]['handler'] = $rManifest['actions'][$k]['handler'];
                $manifest['actions'][$k]['method']  = $rManifest['actions'][$k]['method'];
                $manifest['actions'][$k]['query']   = $rManifest['actions'][$k]['query'];
            }else{
                Log::err('action '.$k.' name is not defined in: '.$modulePath);
            }
        }

        //Filters
        $manifest['filters'] = $rManifest['filters'];

        return $manifest;
    }

    private function loadJson($modulePath){
        $manifestDir = $modulePath . '/manifest.json';
        if (! file_exists($manifestDir)){return null;}
        $jsonStr = file_get_contents($manifestDir);
        $json = json_decode($jsonStr,true);
        return $json;
    }

    private function loadXml($modulePath){
        if (! file_exists($modulePath . "/manifest.xml")) return null;

        $xml = json_decode(json_encode(simplexml_load_file($modulePath . "/manifest.xml")), true);
        $xml['v']    = $xml['@attributes']['v'];
        $xml['info'] = $xml['info']['@attributes'];
        $xml['actions'] = array();

        foreach($xml['action'] as $action){
            if(array_key_exists ('@attributes',$action)){
                $xml['actions'][] = $action['@attributes'];
            }
        }
        unset ($xml['action']);
        unset ($xml['info']['@attributes']);
        unset ($xml['@attributes']);

        return $xml;
    }
    private function actionNameToCamel($actionName){
        $expl = explode('_',strtolower($actionName));
        $ret='';
        foreach($expl as $lit){$ret.=ucfirst($lit);}
        return $ret;
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
        Log::debug('exec ');

        $this->instantiateActions($this->manifest['actions'], $request);
        $rUrl = $this->getActionUrl($request);

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
        if ($dir === null) $dir = $this->getModuleDir();
        if ($namespace === null) $namespace = $this->getNamespace();
        if ($pars === null && array_key_exists('handler',$action))
            $pars = explode(',', $action['handler']);
        elseif($pars === null && !array_key_exists('handler',$action))
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
            Log::debug('loading action \''.$action['name'].'\' Completed');
        }else{
            Log::err('loading action \''.$action['name'].'\' Fails');
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
