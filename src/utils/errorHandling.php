<?php

    namespace Graphene\utils;

    use Graphene\Graphene;
    use Logger;
    use \Exception;

    $haveException = false;


    $__GrapheneDefaultExceptionHandler = function ($e) {
        global $haveException;
        $haveException = true;
        $logger = Graphene::getLogger('graphene_err');
        $logger->error('error: ', $e);
    };

    $__GrapheneErrorHandler = function ($errno,$errstr,$errfile,$errline) {
        global $haveException;
        $haveException = true;
        $logger = Graphene::getLogger('graphene_err');
        $logger->error('error no.' . $errno . ' ' . $errstr . ' at ' . $errfile . ':' . $errline,new Exception());
    };

    $__GrapheneFatalErrorShutdownHandler = function () {
        $logger = Graphene::getLogger('graphene_err');
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
            ],JSON_PRETTY_PRINT));

            global $haveException;
            $haveException = true;
            $logger->error('error no.' . E_ERROR . ' ' . $last_error['message'] . ' at ' . $last_error['file'] . ':' . $last_error['line'],new Exception());
        }
    };


    set_exception_handler($__GrapheneDefaultExceptionHandler);
    register_shutdown_function($__GrapheneFatalErrorShutdownHandler);
    set_error_handler($__GrapheneErrorHandler,E_ALL);

