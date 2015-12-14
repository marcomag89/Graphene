<?php
namespace Graphene\models;

use \Exception;
use Graphene\controllers\Action;
use Graphene\controllers\http\GraphRequest;
use Graphene\controllers\http\GraphResponse;
use Graphene\controllers\ModuleManifest;
use Graphene\Graphene;
use \Settings;
use \Log;

class Module
{

    /**
     * @param  $modulePath
     * @throws \Graphene\controllers\GraphException
     */
    public function __construct($modulePath){
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

    public function getManifestManager(){
        return $this->manifestManager;
    }

    public function getModelDirectory($modelClass){
        return $this->getModuleDir() . '/' . $this->manifest['info']['models-path'] . '/' . $modelClass . '.php';
    }

    private function loadFilters($filters){
        foreach ($filters as $filter) {
            if (file_exists($filter['file'])) {
                require_once $filter['file'];
                $filterClass = $this->namespace . '\\' . $filter['class'];
                $filterClass = new $filterClass();
                $filterClass -> setUp($this, $filter);
                Graphene::getInstance() -> addFilter($filterClass);
            }else{
                Log::err('filter file: '.$filter['file'].' not found');
            }
        }
    }

    public function exec(GraphRequest $request)
    {
        //Log::debug('searching for action in: '.$this->getName());
        //Log::debug(json_encode($this->manifest,JSON_PRETTY_PRINT));
        $this->instantiateActions($this->manifest['actions'], $request);

        foreach ($this->actions as $action) {
            $this->currentAction = $action;
            if ($action->isHandled()) {
                Graphene::getInstance()->stopStat('DispatchingTime',$request->getMethod().' '.$request->getUrl().' '.$request->getContextPar('dispatchingId'));
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
        //Log::debug('loading action \''.$action['unique-name']);
        if($action['imported']==='true')$namespace = 'imports';
        else $namespace = $this->getNamespace();

        $file        = $action['file'];
        $class       = $namespace . '\\' .$action['class'];

        if (file_exists($file)) {
            /** @noinspection PhpIncludeInspection */
            require_once $file;
            if(class_exists($class)){
                $actionClass  =  new $class();
                if($actionClass instanceof Action){
                    $actionClass  -> setUp($this, $action, $request);
                    $this->actions[] = $actionClass;
                    Log::debug('Action '.str_pad($action['unique-name'],50).' loaded');
                }else{
                    Log::err('Action '.str_pad($action['unique-name'],50).' not loaded'.str_pad('',10).'handler class '.$class.' is not an instance of Action in '.$file);
                }
            }else{
                Log::err('Action '.str_pad($action['unique-name'],50).' not loaded'.str_pad('',10).' handler class '.$class.' not found in '.$file);
            }
        }else{
            Log::err('Action '.str_pad($action['unique-name'],50).' not loaded'.str_pad('',10).' handler file '.$file.' not found');
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
    public function getActionDocs($advanced=false,$detail=false)
    {
        $this->instantiateActions($this->manifest['actions'], new GraphRequest());
        $protocol = stripos($_SERVER['SERVER_PROTOCOL'], 'https') === true ? 'https://' : 'http://';
        $serverUrl = $protocol . $_SERVER['HTTP_HOST'] . Graphene::getInstance()->getSettings()['baseUrl'];

        //$baseUrl= $_SERVER['SERVER_NAME'].Settings::getInstance()->getPar('baseUrl');
        //if(!str_starts_with($baseUrl,'http://'))$baseUrl='http://'.$baseUrl;
        $ret=array();
        foreach ($this->actions as $action) {
            if($action instanceof Action){
                $index = count($ret);
                $ret[$index] = ["name"   => $action->getUniqueActionName(),];
                if($advanced){
                    $ret[$index]['method']        = $action->getHandlingMethod();
                    $ret[$index]['url'] = $serverUrl . '/' . $action->getActionUrl();
                    $ret[$index]['module']        = $action->getOwnerModule()->getName();
                    $ret[$index]['interface']     = $action->getActionInterface();

                    $reqBody = $action->getRequestStruct();
                    $resBody = $action->getResponseStruct();
                    if($reqBody !== null) $ret[$index]['request-data']  = $reqBody;
                    if($resBody !== null) $ret[$index]['response-data'] = $resBody;
                }
                if($detail){
                    $ret[$index]['description'] = $action->getDescription();
                }
            }
        }
        return $ret;
    }

    public function getDipendences(){
        return $this->manifest['info']['depends'];
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