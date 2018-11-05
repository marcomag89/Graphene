<?php

    namespace Graphene;

    date_default_timezone_set('Europe/Rome');


    /** @noinspection PhpIncludeInspection */
    include_once join(DIRECTORY_SEPARATOR,[__DIR__,'utils','errorHandling.php']);

    /** @noinspection PhpIncludeInspection */
    include_once join(DIRECTORY_SEPARATOR,[__DIR__,'utils','autoloaders.php']);


    use Graphene\db\CrudDriver;
    use Graphene\models\Module;
    use Graphene\utils\Settings;
    use Graphene\utils\Strings;
    use Graphene\utils\Paths;
    use Graphene\controllers\exceptions\GraphException;
    use Graphene\controllers\http\GraphRequest;
    use Graphene\controllers\http\GraphResponse;
    use Graphene\controllers\GrapheneRouter;
    use Graphene\controllers\Filter;
    use Graphene\controllers\FilterManager;
    use Graphene\db\CrudStorage;
    use Logger;

    //use \Log;


    /**
     * Graphene Framework
     * Graphene framework permette la creazione di servizi RESTful basandosi su un
     * approccio ad azioni
     *
     * Classe Singletone fornito un metodo getInstance()
     *
     * @author Marco Magnetti <marcomagnetti@gmail.com>
     *
     */
    class Graphene {
        const VERSION = '0.3.3 rc1';
        const V_NAME = 'aluminium';
        const INFO = 'Graphene 0.3.3 rc1 [aluminium] developed by Marco Magnetti [marcomagnetti@gmail.com]';
        private static $LOGGER = null;
        private static $GRAPHENE_DIR = __DIR__;

        private static $instance = null;
        /**
         * @var Settings
         */
        private $settings;
        private $startTime,$endTime;
        private $filterManager;
        private $requests;
        private $router;
        private $storage;
        private $systemToken;
        private $stats;
        private $requestId;
        private $initialFile;

        public static function getDirectory() {
            return self::$GRAPHENE_DIR;
        }

        /**
         * @param null $label
         * @return Logger
         */
        public static function getLogger($label = null) {
            $ret = null;
            if ($label != null) {
                $ret = Logger::getLogger($label);
            } else {
                if (self::$LOGGER == null) {
                    Logger::configure(Graphene::getInstance()->getSettings()->get('logging'));
                    self::$LOGGER = Logger::getLogger('graphene_main');

                }
                $ret = self::$LOGGER;
            }

            return $ret;
        }


        private function __construct($configuration = null) {
            //retrieve initial file
            $stack = debug_backtrace();
            $firstFrame = $stack[count($stack) - 1];
            $this->initialFile = $firstFrame['file'];

            $this->settings = Settings::load($configuration);

            $this->startTime = round(microtime(true) * 1000);
            $this->stats = [];
            $this->systemToken = uniqid('SYS_') . $this->startTime;
            ini_set('memory_limit','512M');
            if ($this->isDebugMode()) {
                error_reporting(E_ALL);
                ini_set('opcache.enabled',0);
                ini_set('display_errors','on');
                ini_set('display_startup_errors','on');
            } else {
                error_reporting(E_STRICT);
                ini_set('opcache.enabled',1);
                ini_set('display_errors','off');
                ini_set('display_startup_errors','off');
            }
        }

        public function getInitialFile() {
            return $this->initialFile;
        }

        public function isDebugMode() {
            return $this->settings->get('debug',false) === true;
        }

        /**
         * Recupera l'istanza del framework, o ne crea una nel caso non esista
         *
         * @return Graphene instance
         *
         */
        public static function getInstance($settings = null) {
            if (self::$instance == null) {
                self::registerAutoloaders();
                self::$instance = new Graphene($settings);
                //self::getLogger()->info('Starting Graphene!');
                //\Log::debug("olle!");
            }

            return self::$instance;
        }

        /**
         * Registra gli autoloaders basati su namespace o su modulo
         */
        public static function registerAutoloaders() {
            spl_autoload_register("autol_moduleResources");

            /*
                    spl_autoload_register("autol_namespace");
                    spl_autoload_register("autol_db_drivers");
                    spl_autoload_register("autol_models");
            */
        }

        public static function host() {
            return $_SERVER['SERVER_NAME'];
        }

        /**
         * Questo metodo instanzia il ROUTER lo storage nativo e recupera le
         * informazioni base
         * per generare la richiesta
         */
        public function start() {
            //sleep(2);
            ignore_user_abort(true);
            set_time_limit(0);

            $this->requests = [];
            $this->requestId = uniqid("RID");
            $this->createRequest();
            $request = $this->getRequest();
            Graphene::getLogger()->info(str_pad($request->getMethod(),8,' ') . ' ' . str_pad($request->getUrl(),50,' ') . '     ' . str_pad($_SERVER['REMOTE_ADDR'],16,' ') . ' ' . '    ');
            $this->filterManager = new FilterManager();
            $this->router = new GrapheneRouter($request);
            $crudDriver = $this->getSettings()->getSettingsArray()['storageConfig']['driver'];
            $this->storage = new CrudStorage(new $crudDriver($this->getSettings()->getSettingsArray()['storageConfig']));
            $response = $this->router->dispatch($this->getRequest());
            $this->sendResponse($response);
        }

        /**
         * Crea un istanza di GraphRequest basandosi sulla richiesta ricevuta
         *
         * @return GraphRequest request
         */
        private function createRequest() {
            $req = new GraphRequest();
            $req->setUrl(Paths::getRelativeRequestUrl());
            $req->setIp($_SERVER['REMOTE_ADDR']);
            $req->setMethod($_SERVER['REQUEST_METHOD']);
            if (count($_POST) > 0 || count($_FILES) > 0) {
                $tree = $this->treeFromFlat(array_merge($_FILES,$_POST,$_GET));
                $req->setData($tree);
            } else {
                $jsonData = json_decode(file_get_contents("php://input"),true);
                if ($jsonData === null) {
                    $jsonData = [];
                }
                $req->setData(array_merge($this->treeFromFlat($_GET),$jsonData));
            }
            $headers = apache_request_headers();
            foreach ($headers as $header => $value) {
                $req->setHeader($header,$value);
            }

            $this->pushRequest($req);
        }

        public static function treeFromFlat($rows) {
            $res = [];
            foreach ($rows as $k => $v) {
                $expl = explode('_',$k);
                $tRes = &$res;
                if (count($expl) > 1) {
                    // goto leaf
                    foreach ($expl as $e) {
                        if (!isset($tRes[$e])) {
                            $tRes[$e] = [];
                        }
                        $tRes = &$tRes[$e];
                    }
                    // Popolate leaf
                    $tRes = $v;
                } else {
                    $tRes[$k] = $v;
                }
            }

            return $res;
        }

        private function pushRequest(GraphRequest $request) {
            array_push($this->requests,$request);
        }

        public function getRequest() {
            $current = end($this->requests);

            return $current;
        }

        /**
         * @return Settings
         */
        public function getSettings() {
            return $this->settings;
        }

        /**
         * Invia una risposta al client in base a un istanza di GraphResponse
         *
         * @param GraphResponse $response
         */
        private function sendResponse(GraphResponse $response) {
            $this->supportCors();
            http_response_code($response->getStatusCode());
            $h = $response->getHeaders();
            if (!headers_sent()) {
                foreach ($h as $khdr => $hdr) {
                    header($khdr . ': ' . $hdr);
                }
            }

            //$this->supportCors();
            if ($response->getMedia() !== null) {
                // open the file in a binary mode
                $name = $response->getMedia();
                $fp = fopen($name,'rb');
                fpassthru($fp);
            } else {
                print(json_encode($response->getData(),JSON_PRETTY_PRINT));
            }
        }

        public function supportCors() {
            // Allow from any origin

            if (!headers_sent() && !Strings::startsWith($_SERVER['SERVER_SOFTWARE'],"Microsoft-IIS")) {
                if (isset($_SERVER['HTTP_ORIGIN'])) {
                    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
                    header('Access-Control-Allow-Credentials: true');
                    // header('Access-Control-Max-Age: 86400'); // cache for 1 day
                }

                // Access-Control headers are received during OPTIONS requests
                if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
                    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
                        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE, PATCH");
                    }

                    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
                        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
                    }

                    exit(0);
                }
            }
        }

        public function getApplicationName() {
            return (string)$this->getSettings()->get('appName','graphene-app');
        }

        /**
         * @return CrudStorage
         */
        public function getStorage() {
            return $this->storage;
        }

        /**
         * Esegue il forwarding della richiesta per ottenere informazioni da altri moduli
         *
         * @param GraphRequest $request
         *
         * @return GraphResponse
         * @throws GraphException
         */
        public function forward(GraphRequest $request) {
            if (!Strings::startsWith($request->getUrl(),"http://")) {
                $this->pushRequest($request);
                $resp = $this->router->dispatch($request);
                $this->popRequest();

                return $resp;
            } else {
                if (!extension_loaded('curl')) {
                    throw new GraphException('request forwarding exception: cUrl extension is not installed',5000,500);
                }
                $fp = fsockopen($request->getHost(),80,$errno,$errstr,30);
                if (!$fp) {
                    throw new GraphException('request forwarding exception: ' . $errstr ($errno),5001,500);
                } else {
                    $msg = "GET /" . $request->getPathname() . " HTTP/1.0\r\nHost: " . $request->getHost() . "\r\n\r\n";
                    fwrite($fp,$msg);
                    $resString = '';
                    while (!feof($fp)) {
                        $resString .= fgets($fp,128);
                    }
                    fclose($fp);

                    return $this->parseResponse($resString);
                }
            }
        }

        private function popRequest() {
            array_pop($this->requests);
        }

        private function parseResponse($str) {
            $res = new GraphResponse();
            $schema = explode("\r\n\r\n",$str);
            $res->setBody($schema[1]);
            $status = explode(' ',explode("\n",$schema[0])[0])[1];
            $res->setStatusCode($status);
            $headers = explode("\n",$schema[0]);
            unset($headers[0]);
            foreach ($headers as $h) {
                $kv = explode(':',$h);
                $res->setHeader($kv[0],$kv[1]);
            }

            return $res;
        }

        public function getCurrentModule() {
            return $this->router->getCurrentModule();
        }

        public function getInstalledModulesInfos() {
            $mods = $this->router->getInstalledModules();
            $ret = [];
            foreach ($mods as $mod) {
                $modInfos = [];
                $modInfos['name'] = $mod->getName();
                $modInfos['author'] = $mod->getAuthor();
                $modInfos['support'] = $mod->getSupport();
                $modInfos['version'] = $mod->getVersion();
                $modInfos['namespace'] = $mod->getNamespace();
                $modInfos['actions'] = [];
                foreach ($mod->getActionDocs() as $doc) {
                    $modInfos['actions'][] = $doc['name'];
                }
                $ret[] = $modInfos;
            }

            return $ret;
        }

        /**
         * @param $namespace
         *
         * @return Module | Bool
         */
        public function getModule($namespace) {
            if ($this->router != null) {
                $mod = $this->router->getModuleByNamespace($namespace);
                if ($mod == null) {
                    return false;
                } else {
                    return $mod;
                }
            } else {
                return false;
            }
        }

        public function addFilter(Filter $filter) {
            $this->filterManager->addFilter($filter);
        }

        public function getFilterManager() {
            return $this->filterManager;
        }

        public function getRouter() {
            return $this->router;
        }

        public function getSystemToken() {
            return $this->systemToken;
        }

        public function getDoc($actionName,$detail) {
            $mods = $this->router->getInstalledModules();
            $ret = [];
            foreach ($mods as $mod) {
                if ($mod instanceof Module) {
                    $modAct = $mod->getActionDocs(true,$detail);
                    foreach ($modAct as $action) {
                        if ($action['name'] === $actionName) {
                            $ret[] = $action;
                        }
                    }
                }
            }

            return $ret;
        }

        public function startStat($statName,$statId = null) {
            if (!$this->settings->get('stats')) {
                return;
            }
            if (!array_key_exists($statName,$this->stats)) {
                $this->stats[$statName] = ['main' => []];

            }
            if ($statId === null) {
                $statId = uniqid('stat_');
            }
            $this->stats[$statName]['main']['last'] = $statId;
            $this->stats[$statName][$statId] = [];
            $this->stats[$statName][$statId]['begin'] = round(microtime(true) * 1000);
        }

        public function stopStat($statName,$statId = null) {
            if (!$this->settings->get('stats')) {
                return;
            }
            if ($statId === null) {
                $statId = $this->stats[$statName]['main']['last'];
            }
            $this->stats[$statName][$statId]['end'] = round(microtime(true) * 1000);
            $this->stats[$statName][$statId]['time'] = $this->stats[$statName][$statId]['end'] - $this->stats[$statName][$statId]['begin'];
            $this->stats[$statName]['main']['totalTime'] = $this->stats[$statName]['main']['totalTime'] += $this->stats[$statName][$statId]['time'];
            $this->stats[$statName]['main']['count']++;
            $this->stats[$statName]['main']['average'] = $this->stats[$statName]['main']['totalTime'] / $this->stats[$statName]['main']['count'];

        }

        public function getStats($calculateAverages = false) {
            return ['Graphene Stats' => $this->stats];
        }

        public function getRequestId() {
            return $this->requestId;
        }
    }
