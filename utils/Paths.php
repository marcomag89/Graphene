<?php

namespace Graphene\utils;

use Graphene\Graphene;
use Graphene\utils\Strings;

/**
 * Created by IntelliJ IDEA.
 * User: marco
 * Date: 21/05/17
 * Time: 02:25
 */
class Paths
{
    public static function path($path)
    {
        $path = self::normalizeDirectorySeparator($path);
        if (self::isAbsolute($path) && (is_readable($path) || is_dir($path))) {
            return $path;
        } else {
            $initialFileExpl = explode(DIRECTORY_SEPARATOR, Graphene::getInstance()->getInitialFile());
            array_pop($initialFileExpl);
            $resultPath = join(DIRECTORY_SEPARATOR, $initialFileExpl) . DIRECTORY_SEPARATOR . $path;

            return $resultPath;
        }
    }

    public static function requirePath($path)
    {
        $absolute = self::path($path);
        if (is_readable($absolute)) {
            //Log::debug('COMPLETED Require of: '.$absolute);
            /** @noinspection PhpIncludeInspection */
            require_once $absolute;
        } else {
            Log::err('FAILED Require of: ' . $absolute);
        }
    }

    public static function isAbsolute($path)
    {
        //$win = 'Windows NT';
        $name = php_uname('s');
       // Graphene::getLogger()->debug($name);
        if (Strings::contains(strtolower($name), 'windows')) {
            return preg_match("/^[A-Z]{1}:/", $path) === 1;
        } else {
            return Strings::startsWith(trim($path), '/');
        }
    }

    public static function urlTrimAndClean($url)
    {
        $url = trim($url);
        if (Strings::contains($url, '?')) $url = explode('?', $url)[0];
        if (Strings::startsWith($url, '/')) $url = substr($url, 1);
        if (Strings::endsWith($url, '/')) $url = substr($url, 0, strlen($url) - 1);

        $url = strtolower($url);

        return $url;
    }

    public static function getAbsoluteFromScript($path)
    {
        if (self::isAbsolute($path)) return $path;
        else {
            $mainScript = self::normalizeDirectorySeparator($_SERVER['SCRIPT_FILENAME']);

            $mainScriptExpl = explode(DIRECTORY_SEPARATOR, $mainScript);
            array_pop($mainScriptExpl);
            $mainScriptRoot = join(DIRECTORY_SEPARATOR, $mainScriptExpl);
            $ret = $mainScriptRoot . DIRECTORY_SEPARATOR . $path;
            return $ret;
        }
    }

    public static function getRelativeRequestUrl()
    {
        $reqUri = $_SERVER["REQUEST_URI"];
        $base = Graphene::getInstance()->getSettings()->get('baseUrl', '/');

        if (Strings::startsWith($reqUri, $base)) {
            $reqUri = substr($reqUri, strlen($base));
        }
        $reqUri = '/' . self::urlTrimAndClean($reqUri);
        return $reqUri;
    }

    function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir . "/" . $object) == "dir")
                        rrmdir($dir . "/" . $object);
                    else
                        unlink($dir . "/" . $object);
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }

    public static function normalizeDirectorySeparator($pPath)
    {
        $retValue = '';
        // checks if mainScript uses DIRECTORY_SEPARATOR.
        if (strpos($pPath, DIRECTORY_SEPARATOR) === false) {
            // Normalize DIRECTORY_SEPARATOR if it isn't present.
            $retValue = str_replace((DIRECTORY_SEPARATOR == '/' ? '\\' : '/'), DIRECTORY_SEPARATOR, $pPath);
        } else {
            $retValue = $pPath;
        }//end if

        return $retValue;
    }//end normalize_directory_separator
}