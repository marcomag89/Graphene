<?php
namespace Graphene\controllers;

use Graphene\Graphene;
use Graphene\controllers\http\GraphRequest;
use Graphene\controllers\http\GraphResponse;
use Graphene\models\Module;
use \Exception;
use Graphene\controllers\exceptions\GraphException;
use \Log;

abstract class Action
{

    final public function setUp(Module $ownerModule, $actionSettings, GraphRequest $request)
    {
        $this->actionSettings = $actionSettings;
        $this->urlProcessor   = new UrlProcessor($actionSettings['query']);
        $this->handlingMethod = $this->actionSettings['method'];
        $this->actionName     = self::getStandardActionName($this->actionSettings['name']);
        $this->pars           = $this->actionSettings['pars'];
        $this->request        = $request;
        $this->ownerModule    = $ownerModule;
    }

    final public function isHandled()
    {
        $tests=array();
        $ret = ($tests['method']   = strcasecmp($this->request->getMethod(), $this->handlingMethod) === 0) &&
               ($tests['query']    = $this->checkQuery())   &&
               ($tests['handling'] = $this->checkHandled()) &&
               ($tests['filters']  = $this->checkFilters());
        Log::debug('test results for '.$this->getUniqueActionName().': '.json_encode($tests));
        return $ret;
    }

    final private function checkFilters()
    {

        $filterManager = Graphene::getInstance()->getFilterManager();
        if (! $filterManager->execFilters($this->request, $this->ownerModule, $this)) {
            $this->onFilterFails($filterManager);
            return false;
        }
        return true;
    }

    public function onFilterFails($filterManager)
    {}

    final public function start()
    {
        $startId=uniqid();
        $this->response = new GraphResponse();
        $this->response->setHeader('content-type', 'application/json');
        try {
            Graphene::getInstance()->startStat('Action run','['.$startId.'] '.$this->getUniqueActionName());
            $this->run();
            Graphene::getInstance()->stopStat('Action run','['.$startId.'] '.$this->getUniqueActionName());
        } catch (Exception $e) {
            Graphene::getInstance()->stopStat('Action run','['.$startId.'] '.$this->getUniqueActionName());
            $this->onError($e);
        }
        return $this->response;
    }

    public function getHttpCode($e)
    {
        return 500;
    }

    public function onError($e)
    {
        if ($e instanceof GraphException)
            $this->sendError($e->getCode(), $e->getMessage(), $e->getHttpCode());
        else 
            if ($e instanceof Exception)
                $this->sendError($e->getCode(), $e->getMessage(), $e->getCode());
            else
                $this->sendError(5001, 'internal server error', 500);
    }

    private function checkQuery()
    {
        $rel = $this->ownerModule->getActionUrl($this->request);
        if ($this->urlProcessor->matches($rel)) {
            $this->request->setPars($this->urlProcessor->getPars());
            return true;
        } else
            return false;
    }

    public function getUniqueActionName()
    {
        return strtoupper($this->ownerModule->getNamespace()) . '.' . $this->actionName;
    }

    public function getActionUrl(){
        $q='';
        if(array_key_exists('query',$this->actionSettings)){
            $q='/'.$this->actionSettings['query'];
        }
        return strtolower($this->ownerModule->getNamespace().$q);
    }

    public function getActionName()
    {
        return $this->actionName;
    }

    public function getHandlingMethod()
    {
        return $this->handlingMethod;
    }

    public function getHandlingQuery()
    {
        return $this->handlingQuery;
    }

    public function getOwnerModule()
    {
        return $this->ownerModule;
    }
    public function getDescription(){
        if($this->doc === null && file_exists($this->actionSettings['doc'])){
            $this->doc = file_get_contents($this->actionSettings['doc']);
        }else{
            $this->doc='unavailable doc in: '.$this->actionSettings['doc'];
        }
        return $this->doc;
    }

    final function sendError($err_code, $err_message, $httpCode = null)
    {
        if ($httpCode == null)
            $httpCode = $err_code;
        $this->response->setStatusCode($httpCode);
        $unsupported = array(
            "error" => array(
                "message" => $err_message,
                "errorCode" => $err_code
            )
        );
        $this->response->setBody($this->encodeJson($unsupported));
    }

    function sendMessage($message)
    {
        $unsupported = array(
            "message" => array(
                "message" => $message
            )
        );
        $this->response->setBody($this->encodeJson($unsupported));
    }
    function sendModel($model){
        if($model == null){
            throw new GraphException("Model not available", 404, 404);
        }else if($model instanceof \Serializable){
        	$model->onSend();
            $this->response->setBody($model->serialize());
        }else{
            throw new GraphException("Invalid model instance on sendModel", 500, 500);
        }
    }

    function getFramework()
    {
        $fw = Graphene::getInstance();
        return $fw;
    }

    protected function forward($url, $body = null, $method = null)
    {
        $statId=uniqid();
        Graphene::getInstance()->startStat('RequestForwarding',$url.' : '.$statId);
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
        $req->setHeader('forwarded-by', $this->getUniqueActionName());
        $req->appendForward($this);
        /* Forwarding */
        $fw = $this->getFramework();
        $res=$fw->forward($req);
        Graphene::getInstance()->stopStat('RequestForwarding',$url.' : '.$statId);
        return $res;
    }


    function encodeJson($array){
        return json_encode($array, JSON_PRETTY_PRINT);
    }

    protected function checkHandled()
    {
        return true;
    }

    public static function getStandardActionName($actionName)
    {
        return str_replace(' ', '_', strtoupper($actionName));
    }

    public abstract function run();
    public function getRequestStruct() {return null;}
    public function getResponseStruct(){return null;}

    protected $pars;
    private   $doc=null;

    protected $actionSettings;

    /**
     * @var GraphResponse
     */
    protected $response;

    /**
     * @var GraphRequest
     */
    protected $request;

    /**
     * @var Module
     */
    protected $ownerModule;

    /**
     * @var UrlProcessor
     */
    private $urlProcessor;

    protected $actionName;

    protected $handlingMethod;

    protected $handlingQuery;
}