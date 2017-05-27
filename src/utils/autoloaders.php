<?php

use Graphene\Graphene;
use Graphene\utils\Paths;


function autol_namespace($name)
{
    $grapheneDir = Graphene::getDirectory();

    //Rimuovo il primo token in caso fosse specificato il namespace Graphene
    $exploded = explode('\\', $name);
    if ($exploded[0] === 'Graphene') {
        array_shift($exploded);
    }
    $name = join(DIRECTORY_SEPARATOR, [$grapheneDir, join(DIRECTORY_SEPARATOR, $exploded)]);

    $filename = $name;

    if (is_dir($filename)) {
        /** @noinspection PhpIncludeInspection */
        $filename .= DIRECTORY_SEPARATOR . 'impl.php';
    } else {
        $filename .= ".class.php";
    }

    if (is_readable($filename)) {
        error_log("including: " . $filename);
        /** @noinspection PhpIncludeInspection */
        require_once $filename;
    } else {
        $filename = $name . ".php";
        if (is_readable($filename)) {
            error_log("including: " . $filename);
            /** @noinspection PhpIncludeInspection */
            require_once $filename;
        }
    }
}

function autol_moduleResources($name)
{
    $exploded = explode('\\', $name);
    if (($mod = Graphene::getInstance()->getModule($exploded[0])) === false)
        return;
    // if is model
    if (($modelDir = $mod->getModelDirectory($exploded[1])) !== null) {
        if (is_readable($modelDir)) {
            /** @noinspection PhpIncludeInspection */
            require_once $modelDir;
        }
        return;
    }
}