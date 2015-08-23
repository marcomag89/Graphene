<?php
use Graphene\Graphene;

function autol_namespace($name)
{
    $name = str_replace('\\', '/', $name);
    $filename = $name . ".class.php";
    if (is_readable($filename)) {
        /** @noinspection PhpIncludeInspection */
        require_once $filename;}
}

function autol_models($name)
{
    $expl = explode('\\', $name);
    if (($mod = Graphene::getInstance()->getModule($expl[0])) == false) return;
    if (($modelDir = $mod->getModelDirectory($expl[1])) == null) return;
    if (! is_readable($modelDir)) return;
    require_once $modelDir;
}

function autol_moduleContent($name)
{
    $settings = Graphene::getInstance()->getSettings();
    $modPath = $settings['modulesUrl'];
    $name = str_replace('\\', '/', $name);
    $filename = $modPath . "/" . $name . ".php";
    if (is_readable($filename)) {
        require_once $filename;
    }
}