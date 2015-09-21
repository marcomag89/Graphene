<?php
use Graphene\Graphene;

function autol_namespace($name)
{
    $expl=explode('\\',$name);
    if($expl[0]==='Graphene'){
        array_shift($expl);
    }
    $name = join(DIRECTORY_SEPARATOR,$expl);
    $filename = $name . ".class.php";
    if (is_readable(G_path($filename))) {
         G_Require($filename);
    }
}

function autol_models($name)
{
    $expl = explode('\\', $name);
    if (($mod = Graphene::getInstance()->getModule($expl[0])) === false) return;
    if (($modelDir = $mod->getModelDirectory($expl[1])) === null) return;
    if (! is_readable(G_path($modelDir))) return;
    G_Require($modelDir);
}

function autol_moduleContent($name)
{
    $settings = Graphene::getInstance()->getSettings();
    $modPath = $settings['modulesUrl'];
    $name = str_replace('\\', '/', $name);
    $filename = $modPath . "/" . $name . ".php";
    if (is_readable(G_path($filename))) {
        G_Require($filename);
    }
}