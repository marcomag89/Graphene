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

    abstract class Action {

        protected $pars;
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
        protected $actionName;
        protected $handlingMethod;
        protected $handlingQuery;
        /**
         * @var \Logger logger with action name label
         */
        protected $logger;

        private $doc = null;
        /**
         * @var UrlProcessor
         */
        private $urlProcessor;

        final public function setUp(Module $ownerModule,$actionSettings,GraphRequest $request) {
            $this->actionSettings = $actionSettings;
            $this->urlProcessor = new UrlProcessor($actionSettings['query']);
            $this->handlingMethod = $this->actionSettings['method'];
            $this->actionName = self::getStandardActionName($this->actionSettings['name']);
            $this->pars = $this->actionSettings['pars'];
            $this->request = $request;
            $this->ownerModule = $ownerModule;
            $this->logger = Graphene::getLogger($this->getActionName());
        }

        public static function getStandardActionName($actionName) {
            return str_replace(' ','_',strtoupper($actionName));
        }

        final public function isHandled() {
            $tests = [];
            $ret = ($tests['method'] = strcasecmp($this->request->getMethod(),$this->handlingMethod) === 0) && ($tests['query'] = $this->checkQuery()) && ($tests['handling'] = $this->checkHandled()) && ($tests['filters'] = $this->checkFilters());

            //Log::debug('test results for '.$this->getUniqueActionName().': '.json_encode($tests));
            return $ret;
        }

        private function checkQuery() {
            $rel = $this->ownerModule->getActionUrl($this->request);
            if ($this->urlProcessor->matches($rel)) {
                $this->request->setPars($this->urlProcessor->getPars());

                return true;
            } else {
                return false;
            }
        }

        protected function checkHandled() {
            return true;
        }

        final private function checkFilters() {

            $filterManager = Graphene::getInstance()->getFilterManager();
            if (!$filterManager->execFilters($this->request,$this->ownerModule,$this)) {
                $this->onFilterFails($filterManager);

                return false;
            }

            return true;
        }

        public function onFilterFails($filterManager) {
        }

        final public function start() {
            $startId = uniqid();
            $this->response = new GraphResponse();
            $this->response->setHeader('content-type','application/json');
            $this->response->setHeader('graphene-action',$this->getUniqueActionName());
            try {
                $this->beginTransaction();
                Graphene::getInstance()->startStat('Action run','[' . $startId . '] ' . $this->getUniqueActionName());
                $this->run();
                Graphene::getInstance()->stopStat('Action run','[' . $startId . '] ' . $this->getUniqueActionName());
                $this->commit();
            } catch (Exception $e) {
                Graphene::getLogger(Action::class)->error($e);
                Graphene::getInstance()->stopStat('Action run','[' . $startId . '] ' . $this->getUniqueActionName());
                $this->onError($e);
                //Graphene::getlogger()->error($e);
                Graphene::getLogger(Action::class)->info("Action transaction was rolled back!");
                $this->rollback();
            }

            return $this->response;
        }

        public function getUniqueActionName() {
            return strtoupper($this->ownerModule->getNamespace()) . '.' . $this->actionName;
        }

        public abstract function run();

        public function onError($e) {
            $this->sendException($e);
        }

        function sendException($e) {
            if ($e instanceof GraphException) {
                $this->sendError($e->getCode(),$e->getMessage(),$e->getHttpCode());
            } else {
                if ($e instanceof Exception) {
                    $this->sendError($e->getCode(),$e->getMessage(),$e->getCode());
                } else {
                    $this->sendError(5001,'internal server error',500);
                }
            }
        }

        final function sendError($err_code,$err_message,$httpCode = null) {
            if ($httpCode === null) {
                $httpCode = $err_code;
            }
            $this->response->setStatusCode($httpCode);
            $err = [
                "error" => [
                    "message" => $err_message,
                    "code"    => $err_code
                ]
            ];
            $this->sendData($err);
        }

        function sendData($array) {
            if (is_array($array)) {
                $this->response->setData($array);
            }
        }

        public function getHttpCode($e) {
            return 500;
        }

        public function getActionUrl() {
            $q = '';
            if (array_key_exists('query',$this->actionSettings)) {
                $q = '/' . $this->actionSettings['query'];
            }

            return strtolower($this->ownerModule->getNamespace() . $q);
        }

        public function getActionName() {
            return $this->actionName;
        }

        public function getHandlingMethod() {
            return $this->handlingMethod;
        }

        public function getHandlingQuery() {
            return $this->handlingQuery;
        }

        public function getDescription() {
            if ($this->doc === null && file_exists($this->actionSettings['doc'])) {
                $this->doc = file_get_contents($this->actionSettings['doc']);
            } else {
                $this->doc = 'unavailable doc in ' . $this->actionSettings['doc'];
            }

            return $this->doc;
        }

        public function getActionInterface() {
            return ["name" => "STD_ACTION"];
        }

        function send($object = '') {
            if (is_string($object) && file_exists($object)) {
                $this->sendMedia($object);
            } else if (is_string($object)) {
                $this->sendMessage($object);
            } else if (is_array($object)) {
                $this->sendData($object);
            } else if ($object === null || $object instanceof Model || $object instanceof ModelCollection) {
                $this->sendModel($object);
            } else if ($object instanceof GraphException) {
                $this->sendException($object);
            }
        }

        function sendMedia($mediaUrl) {
            if (!file_exists($mediaUrl)) {
                throw new GraphException('media not found',404,404);
            }
            $this->response->setMedia($mediaUrl);
        }

        function sendMessage($message = '') {
            $msg = ["message" => ["message" => $message]];
            $this->sendData($msg);
        }

        function sendModel($model) {
            if ($model == null) {
                throw new GraphException("Model not available",404,404);
            } else if ($model instanceof Model) {
                $model->onSend();
                $this->sendData($model->getData());
            } else if ($model instanceof ModelCollection) {
                $model->onSend();
                $this->sendData($model->getData());
            } else {
                throw new GraphException("Invalid model instance on sendModel",500,500);
            }
        }

        function getFramework() {
            $fw = Graphene::getInstance();

            return $fw;
        }

        function encodeJson($array) {
            return json_encode($array,JSON_PRETTY_PRINT);
        }

        public function getRequestStruct() {
            return null;
        }

        public function getResponseStruct() {
            return null;
        }

        protected function forward($url,$data = null,$method = null,$checkErrors = true) {
            //Statistics
            $statId = uniqid();
            Graphene::getInstance()->startStat('RequestForwarding',$url . ' : ' . $statId);

            $req = new GraphRequest(true);
            $req->setUrl($url);

            //setting http method
            if ($data === null && $method === null) {
                $req->setMethod('GET');
            } else if ($data !== null && $method === null) {
                $req->setMethod('POST');
            } else if ($method !== null) {
                $req->setMethod($method);
            }

            //setting request data
            if ($data === null) {
                $req->setData([]);
            } else if (is_array($data)) {
                $req->setData($data);
            } else if (is_string($data)) {
                $req->setData(json_decode($data,true));
            } else if ($data instanceof Model) {
                $req->setData($data->getData());
            } else if ($data instanceof ModelCollection) {
                $req->setData($data->getData());
            }

            //setting headers
            $headers = $this->request->getHeaders();
            foreach ($headers as $hk => $hv) {
                $req->setHeader($hk,$hv);
            }
            $req->setHeader('forwarded-by',$this->getUniqueActionName());
            $req->appendForward($this);
            $res = Graphene::getInstance()->forward($req);

            Graphene::getInstance()->stopStat('RequestForwarding',$url . ' : ' . $statId);
            if ($checkErrors && $res->getStatusCode() >= 400) {
                $data = $res->getData();
                throw new GraphException($res->getHeader('graphene-action') . ': ' . $data['error']['message'],$data['error']['code'],400);
            }

            return $res;
        }

        protected function storeMedia($mediaNode) {
            if (is_array($mediaNode)) {
                if (array_key_exists('name',$mediaNode) && array_key_exists('type',$mediaNode) && array_key_exists('tmp_name',$mediaNode) && array_key_exists('error',$mediaNode) && array_key_exists('size',$mediaNode) && $mediaNode['error'] === 0) {
                    $flName = md5(uniqid()) . uniqid();
                    $flName = str_replace('/','_',$mediaNode['type']) . '|' . $flName;

                    $flDir = $this->getMediaDir() . DIRECTORY_SEPARATOR . $flName;
                    if (!copy($mediaNode['tmp_name'],$flDir)) {
                        throw new GraphException('cannot import media',500,500);
                    }
                    unset($mediaNode['tmp_name']);

                    $mediaNode['file_name'] = $flName;
                    $mediaNode['file_dir'] = $flDir;

                    return $mediaNode;
                } else {
                    throw new GraphException('media node error',400,400);
                }
            }
        }

        protected function getMediaDir() {
            $mdir = absolute_from_script($this->getOwnerModule()->getModuleDir() . DIRECTORY_SEPARATOR . 'media');
            if (!file_exists($mdir)) {
                mkdir($mdir);
            }

            return $mdir;
        }

        protected function beginTransaction() {
            Graphene::getInstance()->getStorage()->beginTransaction();
        }

        protected function commit() {
            Graphene::getInstance()->getStorage()->commit();
        }

        protected function rollback() {
            Graphene::getInstance()->getStorage()->rollback();
        }

        public function getOwnerModule() {
            return $this->ownerModule;
        }
    }