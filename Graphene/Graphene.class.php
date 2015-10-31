<?php
namespace Graphene;
//Header function
$utilsIncl = join(DIRECTORY_SEPARATOR, array(dirname(__FILE__),'utils','utils.php'));

include_once $utilsIncl;

use \Settings;
use Graphene\controllers\exceptions\GraphException;
use Graphene\controllers\http\GraphRequest;
use Graphene\controllers\http\GraphResponse;
use Graphene\controllers\GrapheneRouter;
use Graphene\controllers\Filter;
use Graphene\controllers\FilterManager;
use Graphene\db\CrudStorage;
use \Log;


/**
 * Graphene Framework
 * Graphene framework permette la creazione di servizi restFul basandosi su un
 * approccio ad azioni
 *
 * Classe Singletone fornito un metodo getInstance()
 *
 * @author Marco Magnetti <marcomagnetti@gmail.com>
 *        
 */

class Graphene
{

    private function __construct()
    {
        $this->startTime = round(microtime(true) * 1000);
        $this->stats=[];
        $this->systemToken = uniqid('SYS_').$this->startTime;
        if ($this->isDebugMode()) {
            error_reporting(E_ALL);
            ini_set('opcache.enabled', 0);
            ini_set('display_errors', 'on');
            ini_set('display_startup_errors', 'on');
        } else {
            error_reporting(E_STRICT);
            ini_set('opcache.enabled', 1);
            ini_set('display_errors', 'off');
            ini_set('display_startup_errors', 'off');
        }
    }

    /**
     * Questo metodo instanzia il ROUTER lo storage nativo e recupera le
     * informazioni base
     * per generare la richiesta
     */
    public function start()
    {
        //sleep(2);
        $this->requests = array();
        $this->createRequest();
        $request=$this->getRequest();
        Log::request($request->getMethod().' '.$request->getUrl().' from '.$_SERVER['REMOTE_ADDR']);
        $this->filterManager = new FilterManager();
        $this->router = new GrapheneRouter($this->getRequest());
        $crudDriver = 'Graphene\\db\\drivers\\' . (string) $this->getSettings()['storageConfig']['driver'];
        $this->storage = new CrudStorage(new $crudDriver($this->getSettings()['storageConfig']));
        $response = $this->router->dispatch($this->getRequest());
        $this->sendResponse($response);
        if(Settings::getInstance()->getPar('stats')) { Log::logLabel('STATS', $this->stats);}
    }
    public static function path($path=null){
        return G_path($path);
    }
    /**
     * Recupera l'istanza del framework, o ne crea una nel caso non esista
     *
     * @return Graphene instance
     *        
     */
    public static function getInstance()
    {
        if (Graphene::$instance == null) {
            Graphene::registerAutoloaders();
            Graphene::$instance = new Graphene();
        }
        return Graphene::$instance;
    }

    /**
     * Registra gli autoloaders basati su namespace o su modulo
     */
    public static function registerAutoloaders()
    {
        spl_autoload_register("autol_db_drivers");
        spl_autoload_register("autol_namespace");
        spl_autoload_register("autol_moduleContent");
        spl_autoload_register("autol_models");
    }

    public function getSettings()
    {
        return Settings::getInstance()->getSettingsArray();
    }

    public function getApplicationName()
    {
        return (string) $this->getSettings()['appName'];
    }

    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * Esegue il forwarding della richiesta per ottenere informazioni da altri
     * moduli
     *
     * @param GraphRequest $request
     * @return GraphResponse
     * @throws GraphException
     */
    public function forward(GraphRequest $request)
    {
        if (! str_starts_with($request->getUrl(), "http://")) {
            $this->pushRequest($request);
            $resp = $this->router->dispatch($request);
            $this->popRequest();
            return $resp;
        } else {
            if (! extension_loaded('curl')){ throw new GraphException('request forwarding exception: cUrl extension is not installed',5000,500); }
            $fp = fsockopen($request->getHost(), 80, $errno, $errstr, 30);
            if (! $fp) { throw new GraphException('request forwarding exception: '.$errstr ($errno),5001,500); }
            else {
                $msg = "GET /" . $request->getPathname() . " HTTP/1.0\r\nHost: " . $request->getHost() . "\r\n\r\n";
                fwrite($fp, $msg);
                $resString = '';
                while (! feof($fp)) { $resString .= fgets($fp, 128);}
                fclose($fp);
                return $this->parseResponse($resString);
            }
        }
    }

    private function parseResponse($str)
    {
        $res = new GraphResponse();
        $schema = explode("\r\n\r\n", $str);
        $res->setBody($schema[1]);
        $status = explode(' ', explode("\n", $schema[0])[0])[1];
        $res->setStatusCode($status);
        $headers = explode("\n", $schema[0]);
        unset($headers[0]);
        foreach ($headers as $h) {
            $kv = explode(':', $h);
            $res->setHeader($kv[0], $kv[1]);
        }
        return $res;
    }

    /**
     * Crea un istanza di GraphRequest basandosi sulla richiesta ricevuta
     *
     * @return GraphRequest request
     */
    private function createRequest()
    {
        $req = new GraphRequest();
        $req->setUrl(G_requestUrl());
        $req->setIp($_SERVER['REMOTE_ADDR']);
        $req->setMethod($_SERVER['REQUEST_METHOD']);
        if(count($_POST) || count($_FILES)>0){
            $tree = $this->treeFromFlat(array_merge($_FILES, $_POST, $_GET));
            $req->setData($tree);
        }else{
            $jsonData = json_decode(file_get_contents("php://input"),true);
            if($jsonData === null)$jsonData=[];
            $req->setData(array_merge($this->treeFromFlat($_GET),$jsonData));
        }
        $headers = apache_request_headers();
        foreach ($headers as $header => $value) {$req->setHeader($header, $value);}

        $this->pushRequest($req);
    }

    /**
     * Invia una risposta al client in base a un istanza di GraphResponse
     *
     * @param GraphResponse $response
     */
    private function sendResponse(GraphResponse $response)
    {
        $this->supportCors();
        http_response_code($response->getStatusCode());
        $h = $response->getHeaders();
        foreach ($h as $khdr => $hdr) {header($khdr . ': ' . $hdr);}

        //$this->supportCors();
        if($response->getMedia() !== null){
            // open the file in a binary mode
            $name = $response->getMedia();
            $fp = fopen($name, 'rb');
            fpassthru($fp);
        }else{
            print($response->getBody());
        }
    }

    public function supportCors()
    {
        // Allow from any origin
        if (! str_starts_with($_SERVER['SERVER_SOFTWARE'], "Microsoft-IIS")) {
            if (isset($_SERVER['HTTP_ORIGIN'])) {
                header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
                header('Access-Control-Allow-Credentials: true');
                // header('Access-Control-Max-Age: 86400'); // cache for 1 day
            }
            
            // Access-Control headers are received during OPTIONS requests
            if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
                if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
                    header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE, PATCH");
                
                if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
                    header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
                
                exit(0);
            }
        }
    }

    public function getCurrentModule()
    {
        return $this->router->getCurrentModule();
    }

    public function getInstalledModulesInfos()
    {
        $mods = $this->router->getInstalledModules();
        $ret = array();
        foreach ($mods as $mod) {
            $modInfos = array();
            $modInfos['name']      = $mod->getName();
            $modInfos['author']    = $mod->getAuthor();
            $modInfos['support']   = $mod->getSupport();
            $modInfos['version']   = $mod->getVersion();
            $modInfos['namespace'] = $mod->getNamespace();
            $modInfos['actions']   = $mod->getActionDocs();
            $ret[] = $modInfos;
        }
        return $ret;
    }

    /**
     * @param $namespace
     * @return Module | Bool
     */
    public function getModule($namespace)
    {
        if ($this->router != null) {
            $mod = $this->router->getModuleByNamespace($namespace);
            if ($mod == null)
                return false;
            else
                return $mod;
        } else
            return false;
    }

    public static function host(){
        return $_SERVER['SERVER_NAME'];
    }

    public function addFilter(Filter $filter)
    {
        $this->filterManager->addFilter($filter);
    }

    public function getFilterManager()
    {
        return $this->filterManager;
    }

    public function isDebugMode()
    {
        return Settings::getInstance()->getPar('debug') === true;
    }

    private function pushRequest(GraphRequest $request)
    {
        array_push($this->requests, $request);
    }

    private function popRequest()
    {
        array_pop($this->requests);
    }

    public function getRequest()
    {
        $current = end($this->requests);
        return $current;
    }

    public function getRouter()
    {
        return $this->router;
    }
    public function getSystemToken(){
        return $this->systemToken;
    }

    public function getDoc($actionName){
        $mods = $this->router->getInstalledModules();
        foreach($mods as $mod){
            $modAct = $mod->getActionDocs(true);
            foreach($modAct as $action){
                if($action['name'] === $actionName){
                    return $action;
                }
            }
        }
    }

    public function startStat($statName,$statId=null){
        if(!Settings::getInstance()->getPar('stats'))return;
        if(!array_key_exists($statName,$this->stats)){
            $this->stats[$statName]=['main'=>[]];

        }
        if($statId === null) $statId = uniqid('stat_');
        $this->stats[$statName]['main']['last'] = $statId;
        $this->stats[$statName][$statId]          = [];
        $this->stats[$statName][$statId]['begin'] = round(microtime(true) * 1000);
    }

    public function stopStat($statName,$statId=null){
        if(!Settings::getInstance()->getPar('stats'))return;
        if($statId === null) $statId = $this->stats[$statName]['main']['last'];
        $this->stats[$statName][$statId]['end']  = round(microtime(true) * 1000);
        $this->stats[$statName][$statId]['time'] = $this->stats[$statName][$statId]['end'] - $this->stats[$statName][$statId]['begin'];
        $this->stats[$statName]['main']['totalTime'] = $this->stats[$statName]['main']['totalTime'] += $this->stats[$statName][$statId]['time'];
        $this->stats[$statName]['main']['count']++;
        $this->stats[$statName]['main']['average']=$this->stats[$statName]['main']['totalTime']/$this->stats[$statName]['main']['count'];

    }

    public function getStats($calculateAverages=false){
        return ['Graphene Stats'=>$this->stats];
    }

    public static function treeFromFlat($rows){
        $res=array();
        foreach ($rows as $k => $v) {
            $expl = explode('_', $k);
            $tRes = &$res;
            if (count($expl) > 1) {
                // goto leaf
                foreach ($expl as $e) {
                    if (! isset($tRes[$e])){$tRes[$e] = array();}
                    $tRes = &$tRes[$e];
                }
                // Popolate leaf
                $tRes = $v;
            } else $tRes[$k] = $v;
        }
        return $res;
    }

    const VERSION = '0.2.3 rc1';
    const V_NAME  = 'aluminium';
    const INFO    = 'Graphene 0.2.3 rc1 [aluminium] developed by Marco Magnetti [marcomagnetti@gmail.com]';

    private $startTime, $endTime;

    private $filterManager;

    private $requests;

    private $router;

    private $storage;

    private $systemToken;
    private $stats;
    private static $instance = null;
}
