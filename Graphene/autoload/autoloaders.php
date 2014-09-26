<?php
use Graphene\Graphene;
include 'utils.php';
/* autoloaders */
function autol_namespace($name)
{
	$name = str_replace('\\', '/', $name);
	$filename = $name . ".class.php";
	if (is_readable($filename)) {
		require_once $filename;
	}
}

function autol_beans ($name)
{
	// modnamespace\beanName;
	$expl = explode('\\', $name);
	if (($mod = Graphene::getInstance()->getModule($expl[0])) == false)return;
	if (($beanDir = $mod->getBeanDirectory($expl[1])) == null)return;
	if (!is_readable($beanDir))return;
	
	require_once $beanDir;
}

function autol_moduleContent($name)
{
	$settings = Graphene::getInstance()->getSettings();
	$modPath = $settings->moduleurl;
	$name = str_replace('\\', '/', $name);
	$filename = $modPath . "/" . $name . ".php";
	if (is_readable($filename)) {
		require_once $filename;
	}
}