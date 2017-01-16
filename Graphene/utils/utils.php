<?php

date_default_timezone_set('Europe/Rome');

/** @noinspection PhpIncludeInspection */
require_once G_path('utils/Settings.php');

/** @noinspection PhpIncludeInspection */
require_once G_path('utils/Log.php');

G_Require('utils/autoloaders.php');

Settings::getInstance();
Log::setUp();

function G_path($path) {
    if (is_absolute_path($path) && is_readable($path)) {
        return $path;
    } else {
        $basePath = dirname(dirname(__FILE__));
        if ($path !== '' && !strpos($path, DIRECTORY_SEPARATOR === 0)) {
            $path = DIRECTORY_SEPARATOR . $path;
        } else if ($path === null) {
            $path = '';
        }

        $splPath = explode(DIRECTORY_SEPARATOR, $path);
        $splBase = explode(DIRECTORY_SEPARATOR, $basePath);

        $retBase = join(DIRECTORY_SEPARATOR, $splBase);
        $retPath = join(DIRECTORY_SEPARATOR, $splPath);

        return $retBase . $retPath;
    }
}

function G_Require($path) {
    $absolute = G_path($path);
    if (is_readable($absolute)) {
        //Log::debug('COMPLETED Require of: '.$absolute);
        /** @noinspection PhpIncludeInspection */
        require_once $absolute;
    } else {
        Log::err('FAILED Require of: ' . $absolute);
    }
}

/* lib basic functions */
function str_starts_with($haystack, $needle) {
    return $needle === "" || strpos($haystack, $needle) === 0;
}

function str_ends_with($haystack, $needle) {
    return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
}

function str_contains($haystack, $needle) {
    return strpos($haystack, $needle) !== false;
}

function is_absolute_path($path) {
    $win = 'Windows NT';
    $name = php_uname('s');
    if ($name === $win) {
        return preg_match("/^[A-Z]{1}:/", $path) === 1;
    } else {
        return str_starts_with($path, '/');
    }
}

function G_requestUrl() {
    $reqUri = $_SERVER["REQUEST_URI"];
    $base = Settings::getInstance()->getPar('baseUrl');
    if (str_starts_with($reqUri, $base)) {
        $reqUri = substr($reqUri, strlen($base));
    }
    $reqUri = '/' . url_trimAndClean($reqUri);

    return $reqUri;
}

function url_trimAndClean($url) {
    $url = trim($url);
    if (str_contains($url, '?')) {
        $url = explode('?', $url)[0];
    }
    if (str_starts_with($url, '/')) {
        $url = substr($url, 1);
    }
    if (str_ends_with($url, '/')) {
        $url = substr($url, 0, strlen($url) - 1);
    }
    $url = strtolower($url);

    return $url;
}

function absolute_from_script($path) {
    if (is_absolute_path($path)) {
        return $path;
    } else {
        $mainScript = $_SERVER['SCRIPT_FILENAME'];
        $mainScriptExpl = explode(DIRECTORY_SEPARATOR, $mainScript);
        array_pop($mainScriptExpl);
        $mainScriptRoot = join(DIRECTORY_SEPARATOR, $mainScriptExpl);
        $ret = $mainScriptRoot . DIRECTORY_SEPARATOR . $path;

        return $ret;
    }
}

function default_exception_handler($e) {
    Log::err($e);
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

function error_handler($errno, $errstr, $errfile, $errline) {
    global $haveException;
    $haveException = true;
    try {
        throw new Exception('error no.' . $errno . ' ' . $errstr . ' at ' . $errfile . ':' . $errline);
    } catch (Exception $e) {
        throw new Exception($e);
    }
}

function fatalErrorShutdownHandler() {
    $last_error = error_get_last();
    if ($last_error['type'] === E_ERROR) {
        // fatal error
        header('Content-Type: application/json');
        http_response_code(500);
        print(json_encode([
                              "fatal" => [
                                  'message' => 'sorry, we have an unknown problem. Check the Graphene errors log',
                                  'code'    => '500'
                              ]
                          ], JSON_PRETTY_PRINT));;
        error_handler(E_ERROR, $last_error['message'], $last_error['file'], $last_error['line']);
    }
}

function rrmdir($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (filetype($dir . "/" . $object) == "dir") {
                    rrmdir($dir . "/" . $object);
                } else {
                    unlink($dir . "/" . $object);
                }
            }
        }
        reset($objects);
        rmdir($dir);
    }
}

$haveException = false;

set_exception_handler("default_exception_handler");
register_shutdown_function('fatalErrorShutdownHandler');
set_error_handler("error_handler", E_ALL);

