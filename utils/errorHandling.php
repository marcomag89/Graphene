<?php

namespace Graphene\utils;

use Graphene\Graphene;

function default_exception_handler(Exception $e)
{
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

function error_handler($errno, $errstr, $errfile, $errline)
{
    global $haveException;
    $haveException = true;
    $logger = Graphene::getLogger();
    $logger->
    Log::err('error no.' . $errno . ' ' . $errstr . ' at ' . $errfile . ':' . $errline);
}

function fatalErrorShutdownHandler()
{
    $last_error = error_get_last();
    if ($last_error['type'] === E_ERROR) {
        // fatal error
        header('Content-Type: application/json');
        http_response_code(500);
        print(json_encode([
            "fatal" => [
                'message' => 'sorry, we have an unknown problem. Check the Graphene errors log',
                'code' => '500'
            ]
        ], JSON_PRETTY_PRINT));;
        error_handler(E_ERROR, $last_error['message'], $last_error['file'], $last_error['line']);
    }
}


$haveException = false;

set_exception_handler("default_exception_handler");
register_shutdown_function('fatalErrorShutdownHandler');
set_error_handler("error_handler", E_ALL);

