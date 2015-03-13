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
    if (str_starts_with($url, '/'))
        $url = substr($url, 1);
    if (str_ends_with($url, '/'))
        $url = substr($url, 0, strlen($url) - 1);
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

function log_write($what)
{
    global $log;
    global $stdout;
    if ($stdout == null)
        $stdout = fopen('php://stdout', 'w');
    fputs($stdout, $what);
    $log .= "\n\t" . $what;
    // echo "\n".$what;
}

function log_print()
{
    global $log;
    echo $log;
}

function init_platform()
{
    date_default_timezone_set('Europe/Rome');
}
$haveException = false;
set_exception_handler("default_exception_handler");
