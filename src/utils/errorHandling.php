<?php

namespace Graphene\utils;

use Graphene\Graphene;
use Logger;

$__GrapheneDefaultExceptionHandler = function (Exception $e) {
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
};

$__GrapheneErrorHandler = function ($errno, $errstr, $errfile, $errline) {
    global $haveException;
    $haveException = true;
    $logger = Graphene::getLogger('graphene_err');
    $logger->error('error no.' . $errno . ' ' . $errstr . ' at ' . $errfile . ':' . $errline);
};

$__GrapheneFatalErrorShutdownHandler = function () {
    global $__GrapheneErrorHandler;
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
        call_user_func($__GrapheneErrorHandler, E_ERROR, $last_error['message'], $last_error['file'], $last_error['line']);
    }
};


$haveException = false;

set_exception_handler($__GrapheneDefaultExceptionHandler);
register_shutdown_function($__GrapheneFatalErrorShutdownHandler);
set_error_handler($__GrapheneErrorHandler, E_ALL);

