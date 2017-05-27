<?php

    require_once __DIR__ . DIRECTORY_SEPARATOR . 'Log.php';
    require_once __DIR__ . DIRECTORY_SEPARATOR . 'Strings.php';
    require_once __DIR__ . DIRECTORY_SEPARATOR . 'Paths.php';

    use Graphene\Graphene;
    use Graphene\utils\Paths;

    try {
        include_once join(DIRECTORY_SEPARATOR,[__DIR__,'..','vendor','autoload.php']);
    } catch (\Exception $e) {
        error_log("Vendor autoloader not found, run './composer install'");
    }

    function autol_namespace($name) {
        $grapheneDir = Graphene::getDirectory();

        //Rimuovo il primo token in caso fosse specificato il namespace Graphene
        $exploded = explode('\\',$name);
        if ($exploded[0] === 'Graphene') {
            array_shift($exploded);
        }
        $name = join(DIRECTORY_SEPARATOR,[$grapheneDir,join(DIRECTORY_SEPARATOR,$exploded)]);

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

    function autol_moduleResources($name) {

        $exploded = explode('\\',$name);
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

        /*    //Log::debug($modelDir);
            if (is_readable(G_path($modelDir))) $modelDir = G_path($modelDir);
            else if (is_readable(absolute_from_script($modelDir))) $modelDir = absolute_from_script($modelDir);
            else return;

            G_Require($modelDir);*/
    }

    /*
    function autol_db_drivers($name)
    {
        $driversPath = 'Graphene\\db\\drivers\\';

        if (Strings::startsWith($name, $driversPath)) {
            $expl = explode('\\', $name);
            if ($expl[0] === 'Graphene') {
                array_shift($expl);
            }
            $name = join(DIRECTORY_SEPARATOR, $expl);

            if (is_dir(Graphene\($name))) {
                G_Require($name . DIRECTORY_SEPARATOR . 'impl.php');
            } else {
                G_Require($name . ".php");
            }
        }
    }*/

    /*function autol_models($name)
    {
        $expl = explode('\\', $name);
        if (($mod = Graphene::getInstance()->getModule($expl[0])) === false) return;
        if (($modelDir = $mod->getModelDirectory($expl[1])) === null) return;
        //Log::debug($modelDir);
        if (is_readable(G_path($modelDir))) $modelDir = G_path($modelDir);
        else if (is_readable(absolute_from_script($modelDir))) $modelDir = absolute_from_script($modelDir);
        else return;

        G_Require($modelDir);
    }

    function autol_moduleContent($name)
    {
        $settings = Graphene::getInstance()->getSettings();
        $modPath = $settings['modulesUrl'];
        $name = str_replace('\\', '/', $name);
        $filename = $modPath . "/" . $name . ".php";
        if (is_readable(Paths($filename))) {
            Paths::requirePath($filename);
        }
    }*/