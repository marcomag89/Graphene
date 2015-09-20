<?php
/** @noinspection PhpIncludeInspection */
require_once G_path('utils/Settings.php');

/** @noinspection PhpIncludeInspection */
require_once G_path('utils/Log.php');

G_Require('utils/autoloaders.php');

function G_path($path){
    if(is_absolute_path($path) && is_readable($path))return $path;
    else{
        $basePath = dirname(dirname(__FILE__));
        if($path !== '' && !strpos($path,DIRECTORY_SEPARATOR === 0)){$path = DIRECTORY_SEPARATOR.$path;}
        else if($path === null){$path='';}

        $splPath  = explode(DIRECTORY_SEPARATOR, $path);
        $splBase  = explode(DIRECTORY_SEPARATOR, $basePath);

        $retBase      = join(DIRECTORY_SEPARATOR, $splBase);
        $retPath      = join(DIRECTORY_SEPARATOR, $splPath);

        return $retBase.$retPath;
    }
}

function G_Require($path){
    $absolute = G_path($path);
    if(is_readable($absolute)){
        Log::debug('COMPLETED Require of: '.$absolute);
        /** @noinspection PhpIncludeInspection */
        require_once $absolute;
    }else{
        Log::err('FAILED Require of: '.$absolute);
    }
}

/* lib basic functions */
function str_starts_with($haystack, $needle) { return $needle === "" || strpos($haystack, $needle) === 0;}
function str_ends_with($haystack, $needle)   { return $needle === "" || substr($haystack, - strlen($needle)) === $needle;}
function str_contains($haystack, $needle)    { return strpos($haystack, $needle) !== false; }

function is_absolute_path($path){
    $win = 'Windows NT';
    $name = php_uname('s');
    if($name === $win){
       return  preg_match("/^[A-Z]{1}:/", $path)===1;
    }else{
        return str_starts_with($path,'/');
    }
}

function G_requestUrl(){
    $reqUri = $_SERVER["REQUEST_URI"];
    url_trimAndClean($reqUri);
    $base=Settings::getInstance()->getPar('baseUrl');
    if(str_starts_with($reqUri,$base)){
        $reqUri = substr($reqUri, strlen($base));
    }
    return $reqUri;
}

function url_trimAndClean($url)
{
    $url = trim($url);
    if (str_starts_with($url, '/')) $url = substr($url, 1);
    if (str_ends_with($url, '/'))   $url = substr($url, 0, strlen($url) - 1);
    $url = strtolower($url);
    return $url;
}

function default_exception_handler(Exception $e)
{
    global $haveException;
    $haveException = true;
    $msg = $e->getMessage();
    echo "Oops qualcosa non va\n";
    echo "Stiamo lavorando duro per risolvere il problema\n";
    echo "[Eccezione] $msg";
    echo "\n----\nStackTrace:\n";
    $st = $e->getTrace();
    foreach ($st as $entry) {
        echo "\t" . 'funct ' . $entry['function'] . '() in ' . $entry['file'] . "\n";
    }
}

function fatal_handler()
{
    global $haveException;
    if ($haveException)
        return;
    echo "OhMio dio!\n";
    echo "Qui abbiamo un problema!\n";
}

function log_write($what){
    $traceStr = Log::getTraceString(debug_backtrace());
    Log::write('LEGACY',$what,$traceStr);
}

function init_platform(){date_default_timezone_set('Europe/Rome');}

$haveException = false;
set_exception_handler("default_exception_handler");
