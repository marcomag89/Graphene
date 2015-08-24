<?php
$log = '';
$stdout = null;

/* lib basic functions */
function str_starts_with($haystack, $needle)
{
    return $needle === "" || strpos($haystack, $needle) === 0;
}

function str_ends_with($haystack, $needle)
{
    return $needle === "" || substr($haystack, - strlen($needle)) === $needle;
}

function str_contains($haystack, $needle)
{
    if (strpos($haystack, $needle) !== false) {
        return true;
    } else
        return false;
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
    
    echo "\n----\nLog\n";
    log_print();
}

function fatal_handler()
{
    global $haveException;
    if ($haveException)
        return;
    echo "OhMio dio!\n";
    echo "Qui abbiamo un problema!\n";
    log_print();
}


class Log{

    public static function setUp($logSettings){
        Log::$err   = $logSettings ['errors'];
        Log::$warn  = $logSettings ['warnings'];
        Log::$all   = $logSettings ['all'];
        Log::$debug = $logSettings ['debug'];
        Log::$req   = $logSettings ['requests'];
        //removing old files
        if(file_exists(Log::$err)) unlink(Log::$err);
        if(file_exists(Log::$warn)) unlink(Log::$warn);
        if(file_exists(Log::$all)) unlink(Log::$all);
        if(file_exists(Log::$debug)) unlink(Log::$debug);
        if(file_exists(Log::$req)) unlink(Log::$req);
    }

    public static function write($label, $object, $traceStr = ''){
        if($traceStr === ''){$traceStr = Log::getTraceString(debug_backtrace());}
        $record   = str_pad('['.$label.' | '.Log::getTimeStirng(). ' | '.$traceStr,60).'] '.$object."\n";
        file_put_contents(Log::$all, $record, FILE_APPEND | LOCK_EX);
    }

    public static function debug($object){
        $traceStr = Log::getTraceString(debug_backtrace());
        $record   = str_pad('['.Log::getTimeStirng().' | '.$traceStr,50).'] '.$object."\n";
        file_put_contents(Log::$debug, $record, FILE_APPEND | LOCK_EX);
        Log::write('DEBUG',$object,$traceStr);
    }

    public static function err ($object){
        $traceStr = Log::getTraceString(debug_backtrace());
        $record   = str_pad('['.Log::getTimeStirng().' | '.$traceStr,50).'] '.$object."\n";
        file_put_contents(Log::$err, $record, FILE_APPEND | LOCK_EX);
        Log::write('ERROR',$object,$traceStr);
    }

    public static function warn ($object){
        $traceStr = Log::getTraceString(debug_backtrace());
        $record   = str_pad('['.Log::getTimeStirng().' | '.$traceStr,50).'] '.$object."\n";
        file_put_contents(Log::$warn, $record, FILE_APPEND | LOCK_EX);
        Log::write('WARNING',$object,$traceStr);
    }

    public static function request ($object){
        $traceStr = Log::getTraceString(debug_backtrace());
        $record   = str_pad('['.Log::getTimeStirng().' | '.$traceStr,50).'] '.$object."\n";
        file_put_contents(Log::$req, $record, FILE_APPEND | LOCK_EX);
        Log::write('REQUEST',$object,$traceStr);
    }

    public static function getTraceString($backtrace){
        $exp = explode('/',$backtrace[0]['file']);
        $filename = $exp[count($exp)-1];
        return $filename.':'.$backtrace[0]['line'];
    }

    public static function getTimeStirng(){
        $dt = new DateTime();
        return $dt->format('Y-m-d H:i:s');
    }

    private static $err, $all, $debug, $req, $warn;
}

function log_write($what){
    $traceStr = Log::getTraceString(debug_backtrace());
    Log::write('LEGACY',$what,$traceStr);
}


function init_platform(){date_default_timezone_set('Europe/Rome');}

$haveException = false;
set_exception_handler("default_exception_handler");
