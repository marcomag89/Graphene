<?php
use Graphene\Graphene;

function autol_namespace($name)
{
    $name = str_replace('\\', '/', $name);
    $filename = $name . ".class.php";
    //Log::debug( 'namespace filename: '.$filename);
    if (is_readable($filename)) {
        /** @noinspection PhpIncludeInspection */
        require_once $filename;
    }

}

function autol_models($name)
{
    $expl = explode('\\', $name);
    if (($mod = Graphene::getInstance()->getModule($expl[0])) === false) return;
    //FIXME getModelDirectory non esistente
    if (($modelDir = $mod->getModelDirectory($expl[1])) === null) return;
    //Log::debug( 'models dir: '.$modelDir);
    if (! is_readable($modelDir)) return;
    require_once $modelDir;
}

function autol_moduleContent($name)
{
    $settings = Graphene::getInstance()->getSettings();
    $modPath = $settings['modulesUrl'];
    $name = str_replace('\\', '/', $name);
    $filename = $modPath . '/' . $name . '.php';
    //Log::debug( 'module filename: '.$filename);
    if (is_readable($filename)) {
        require_once $filename;
    }
}