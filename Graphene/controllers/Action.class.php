<?php
namespace Graphene\controllers;

use Graphene\Graphene;
use Graphene\controllers\http\GraphRequest;
use Graphene\controllers\http\GraphResponse;
use Graphene\models\Model;
use Graphene\models\ModelCollection;
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
        $this->response->setHeader('graphene-action',$this->getUniqueActionName());
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

    public function getHttpCode($e) {return 500;}
    public function onError($e)     {$this->sendException($e);}

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
            $this->doc='unavailable doc in '.$this->actionSettings['doc'];
        }
        return $this->doc;
    }

    function sendException($e){
        if ($e instanceof GraphException) $this->sendError($e->getCode(), $e->getMessage(), $e->getHttpCode());
        else{
            if ($e instanceof Exception) $this->sendError($e->getCode(), $e->getMessage(), $e->getCode());
            else $this->sendError(5001, 'internal server error', 500);
        }
    }

    final function sendError($err_code, $err_message, $httpCode = null){
        if ($httpCode === null) $httpCode = $err_code;
        $this->response->setStatusCode($httpCode);
        $err = ["error"=>
            [
                "message" => $err_message,
                "code"    => $err_code
            ]
        ];
        $this->sendData($err);
    }

    function sendMessage($message = ''){
        $msg = ["message"=>["message"=>$message]];
        $this->sendData($msg);
    }

    function sendModel($model){
        if($model == null){
            throw new GraphException("Model not available", 404, 404);
        }
        else if($model instanceof Model){
        	$model->onSend();
            $this->sendData($model->getData());
        }else if($model instanceof ModelCollection){
            $model->onSend();
            $this->sendData($model->getData());
        }else{
            throw new GraphException("Invalid model instance on sendModel", 500, 500);
        }
    }

    function sendData($array){
        if(is_array($array)){$this->response->setData($array);}
    }

    function sendMedia($mediaUrl){
        if(!file_exists($mediaUrl)) throw new GraphException('media not found',404,404);
        $this->response->setMedia($mediaUrl);
    }

    function send($object = ''){
        if(is_string($object) && file_exists($object)){$this->sendMedia($object);}
        else if(is_string($object)) $this->sendMessage($object);
        else if(is_array($object)){$this->sendArray();}
        else if($object === null || $object instanceof Model || $object instanceof ModelCollection) $this->sendModel($object);
        else if($object instanceof GraphException) $this->sendException($object);
    }

    function getFramework()
    {
        $fw = Graphene::getInstance();
        return $fw;
    }
    protected function forward($url, $data = null, $method = null, $checkErrors=true){
        //Statistics
        $statId = uniqid();
        Graphene::getInstance()->startStat('RequestForwarding',$url.' : '.$statId);

        $req = new GraphRequest(true);
        $req->setUrl($url);

        //setting http method
        if ($data === null && $method === null)      $req->setMethod('GET');
        else if ($data !== null && $method === null) $req->setMethod('POST');
        else if ($method !== null)                   $req->setMethod($method);

        //setting request data
        if($data === null)                        $req->setData([]);
        else if(is_array($data))                  $req->setData($data);
        else if(is_string($data))                 $req->setData(json_decode($data,true));
        else if($data instanceof Model)           $req->setData($data->getData());
        else if($data instanceof ModelCollection) $req->setData($data->getData());

        //setting headers
        $headers = $this->request->getHeaders();
        foreach ($headers as $hk => $hv) {$req->setHeader($hk, $hv);}
        $req->setHeader('forwarded-by', $this->getUniqueActionName());
        $req->appendForward($this);
        $res = Graphene::getInstance()->forward($req);

        Graphene::getInstance()->stopStat('RequestForwarding',$url.' : '.$statId);
        if($checkErrors && $res->getStatusCode() >= 400){
            $data = $res->getData();
            throw new GraphException($res->getHeader('graphene-action').': '.$data['error']['message'],$data['error']['code'],400);
        }
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

    protected function storeMedia($mediaNode){
        if(is_array($mediaNode)){
            if(
                array_key_exists('name',     $mediaNode) &&
                array_key_exists('type',     $mediaNode) &&
                array_key_exists('tmp_name', $mediaNode) &&
                array_key_exists('error',    $mediaNode) &&
                array_key_exists('size',     $mediaNode) &&
                $mediaNode['error'] === 0
            ){
                $flName = md5(uniqid()).uniqid();
                $flDir  = $this->getMediaDir().DIRECTORY_SEPARATOR.str_replace('/','_',$mediaNode['type']).'|'.$flName;
                if(!copy($mediaNode['tmp_name'], $flDir)){
                    throw new GraphException('cannot import media',500,500);
                }
                $mediaNode['file_name'] = $flDir;
                return $flDir;
            }else{
                throw new GraphException('media node error',400,400);
            }
        }
    }

    protected function getMediaDir(){
        $mdir = absolute_from_script($this->getOwnerModule()->getModuleDir().DIRECTORY_SEPARATOR.'media');
        if(!file_exists($mdir)) mkdir($mdir);
       return  $mdir;
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