<?php
namespace Graphene\controllers;

use Graphene\Graphene;
use Graphene\controllers\http\GraphRequest;
use Graphene\controllers\http\GraphResponse;
use Graphene\controllers\UrlProcessor;
use Graphene\models\Module;
use Graphene\models\Model;
use \Exception;
use Graphene\controllers\exceptions\GraphException;
use Graphene\models\ModelCollection;

abstract class Action
{

    final public function setUp(Module $ownerModule, $actionSettings, GraphRequest $request, $pars, $queryPrefix = '')
    {
        // SETTING request query
        if (isset($actionSettings['query']))
            $this->urlProcessor = new UrlProcessor($queryPrefix . $actionSettings['query']);
        else
            $this->urlProcessor = new UrlProcessor($queryPrefix . '');
            
            // SETTING handling method
        if (isset($actionSettings['method']))
            $this->handlingMethod = strtoupper($actionSettings['method']);
        else
            $this->handlingMethod = 'GET';
            
            // SETTING other infos
        $this->actionSettings=$actionSettings;
        $this->pars = $pars;
        $this->request = $request;
        $this->actionName = self::getStandardActionName($actionSettings['name']);
        $this->ownerModule = $ownerModule;
    }

    final public function isHandled()
    {
        return strcasecmp($this->request->getMethod(), $this->handlingMethod) == 0 && $this->checkQuery() && $this->checkFilters() && $this->checkHandled();
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
        $this->response = new GraphResponse();
        $this->response->setHeader('content-type', 'application/json');
        try {
            $this->run();
        } catch (Exception $e) {
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
        if(isset ($this->actionSettings['query'])){ $q='/'.$this->actionSettings['query'];}
        else{ $q='';}
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
    
    function sendModel_old($model)
    {
        if (is_array($model)) {
            if (count($model) == 0) {
                $this->sendError(404, 'resource not found');
            } else 
                if (count($model) == 1 && $model[0] instanceof Model) {
                    $model[0]->onSend();
                    $this->response->setBody(json_encode(json_decode($model[0]->serialize()), JSON_PRETTY_PRINT));
                } else 
                    if (count($model) > 1) {
                        $bodyArr = array(
                            'array' => array()
                        );
                        foreach ($model as $elem) {
                            if ($elem instanceof Model) {
                                $elem->onSend();
                                $bodyArr['array'][] = json_decode($elem->serialize());
                            }
                        }
                        $this->response->setBody(json_encode($bodyArr, JSON_PRETTY_PRINT));
                    }
        } else 
            if ($model instanceof Model) {
                $model->onSend();
                $this->response->setBody(json_encode(json_decode($model->serialize()), JSON_PRETTY_PRINT));
            }
    }

    function getFramework()
    {
        $fw = Graphene::getInstance();
        return $fw;
    }

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
        $req->setHeader('forwarded-by', $this->getUniqueActionName());
        $req->appendForward($this);
        /* Forwarding */
        $fw = $this->getFramework();
        return $fw->forward($req);
    }

    function encodeJson($array)
    {
        return json_encode($array, JSON_PRETTY_PRINT);
    }

    final function getUrl($url)
    {
        return $baseUrl . "/" . $url;
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

    /**
     *
     * @var Module
     */
    protected $ownerModule;

    /**
     *
     * @var GraphRequest
     */
    protected $request;

    /**
     *
     * @var GraphResponse
     */
    protected $pars;

    protected $actionSettings;

    protected $response;

    private $urlProcessor;

    protected $actionName;

    protected $handlingMethod;
}