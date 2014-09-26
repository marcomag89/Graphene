<?php
namespace Graphene;
use Graphene\controllers\ModuleManager;
use Graphene\controllers\RouteCreator;
use Graphene\db\Storage;
use Graphene\controllers\http\GraphRequest;
use Graphene\controllers\http\GraphResponse;
use Graphene\controllers\GrapheneRouter;
use Graphene\controllers\Filter;
use Graphene\controllers\FilterManager;
use Graphene\models\Module;
use Graphene\controllers\Action;
use Graphene\db\CrudStorage;
include 'Graphene/autoload/autoloaders.php';

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

	private function __construct ()
	{
		$this->startTime= round(microtime(true) * 1000);
		$this->settings = simplexml_load_file('Graphene/configs/settings.xml');
		$this->debugMode = strcasecmp($this->settings->debug, 'true') == 0;
		$this->showLog = strcasecmp($this->settings->showLog, 'true') == 0;
		if ($this->isDebugMode()) {
			error_reporting(E_ALL);
			ini_set('opcache.enabled', 0);
		} else {
			error_reporting(0);
		}
	}

	/**
	 * Questo metodo instanzia il ROUTER lo storage nativo e recupera le
	 * informazioni base
	 * per generare la richiesta
	 */
	public function start ()
	{			
		$this->requests = array();
		$this->createRequest();
		$this->filterManager = new FilterManager();
		$this->router = new GrapheneRouter($this->getRequest());
		$crudDriver = 'Graphene\\db\\drivers\\' .(string) $this->settings->storageDriver;
		$this->storage = new CrudStorage(new $crudDriver($this->settings->storageConfig));
		$response = $this->router->dispatch($this->getRequest());
		$this->sendResponse($response);
	}
	/**
	 * Recupera l'istanza del framework, o ne crea una nel caso non esista
	 *
	 * @return Graphene instance
	 *        
	 */
	public static function getInstance ()
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
	public static function registerAutoloaders ()
	{
		spl_autoload_register("autol_namespace");
		spl_autoload_register("autol_moduleContent");
		spl_autoload_register("autol_beans");
		init_platform();
	}

	public function getSettings ()
	{
		return $this->settings;
	}

	public function getApplicationName ()
	{
		return (string) $this->settings->appname[0];
	}

	public function getStorage ()
	{
		return $this->storage;
	}

	/**
	 * Esegue il forwarding della richiesta per ottenere informazioni da altri
	 * moduli
	 */
	public function forward (GraphRequest $request)
	{
		if (! str_starts_with($request->getUrl(), "http://")) {
			$this->pushRequest($request);
			$resp = $this->router->dispatch($request);
			$this->popRequest();
			return $resp;
		} else {
			if (! extension_loaded('curl'))
				return false;
			$fp = fsockopen($request->getHost(), 80, $errno, $errstr, 30);
			if (! $fp) {
				echo "$errstr ($errno)<br>\n";
			} else {
				$msg = "GET /" . $request->getPathname() . " HTTP/1.0\r\nHost: " .
						 $request->getHost() . "\r\n\r\n";
				fputs($fp, $msg);
				$resString = '';
				while (! feof($fp)) {
					$resString .= fgets($fp, 128);
				}
				fclose($fp);
				return $this->parseResponse($resString);
			}
		}
	}

	private function parseResponse ($str)
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
	private function createRequest ()
	{
		$req = new GraphRequest();
		$req = new GraphRequest();
		$req->setUrl($_SERVER["REQUEST_URI"]);
		$req->setMethod($_SERVER['REQUEST_METHOD']);
		$req->setBody(file_get_contents("php://input"));
		$req->setIp($_SERVER['REMOTE_ADDR']);
		$headers = apache_request_headers();
		foreach ($headers as $header => $value) {
			$req->setHeader($header, $value);
		}
		$this->pushRequest($req);
	}

	/**
	 * Invia una risposta al client in base a un istanza di GraphResponse
	 */
	private function sendResponse (GraphResponse $response)
	{
		http_response_code($response->getStatusCode());
		$h = $response->getHeaders();
		foreach ($h as $khdr => $hdr) {
			header($khdr . ': ' . $hdr);
		}
		$this->supportCors();
		header('elabTime:'.(round(microtime(true) * 1000)-$this->startTime).' ms');
		print($response->getBody());
		if ($this->isDebugMode() && $this->showLog()) {
			print("\n---\nLOG");
			log_print();
		}
	}
	public function supportCors(){
		// Allow from any origin
		if(!str_starts_with($_SERVER['SERVER_SOFTWARE'], "Microsoft-IIS")){
			if (isset($_SERVER['HTTP_ORIGIN'])) {
				header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
				header('Access-Control-Allow-Credentials: true');
				//header('Access-Control-Max-Age: 86400');    // cache for 1 day
			}
			
			// Access-Control headers are received during OPTIONS requests
			if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {	
				if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
					header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
			
				if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
					header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
			
				exit(0);
			}
		}
	}
	public function getCurrentModule ()
	{
		return $this->router->getCurrentModule();
	}

	public function getInstalledModulesInfos ()
	{
		$mods = $this->router->getInstalledModules();
		$ret = array();
		foreach ($mods as $mod) {
			$modInfos = array();
			$modInfos['name'] = $mod->getName();
			$modInfos['author'] = $mod->getAuthor();
			$modInfos['author-contact'] = $mod->getAuthorEmail();
			$modInfos['support'] = $mod->getSupport();
			$modInfos['version'] = $mod->getVersion();
			$modInfos['namespace'] = $mod->getNamespace();
			$modInfos['actions'] = $mod->getActionNames();
			$ret[] = $modInfos;
		}
		return $ret;
	}

	public function getModule ($namespace){
		if ($this->router != null) {
			$mod = $this->router->getModuleByNamespace($namespace);
			if ($mod == null)
				return false;
			else
				return $mod;
		} else
			return false;
	}

	public function addFilter (Filter $filter)
	{
		$this->filterManager->addFilter($filter);
	}

	public function getFilterManager ()
	{
		return $this->filterManager;
	}

	public function isDebugMode ()
	{
		return $this->debugMode;
	}

	public function showLog ()
	{
		return $this->showLog;
	}

	private function pushRequest (GraphRequest $request)
	{
		array_push($this->requests, $request);
	}

	private function popRequest ()
	{
		$pop = array_pop($this->requests);
	}

	public function getRequest ()
	{
		$current = end($this->requests);
		return $current;
	}

	const INFO = 'Graphene 0.1b developed by Marco Magnetti <marcomagnetti@gmail.com>';

	const VERSION = '0.1b';
	private $startTime,$endTime;
	private $showLog;
	private $debugMode;
	private $filterManager;
	private $requests;
	private $router;
	private $storage;
	private $settings;
	private $definedRoutes;
	private static $instance = null;
}
