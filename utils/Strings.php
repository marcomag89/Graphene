<?php

namespace Graphene\utils;

/**
 * Created by IntelliJ IDEA.
 * User: marco
 * Date: 21/05/17
 * Time: 02:20
 */
class Strings
{
    public static function startsWith($haystack, $needle)
    {
        return $needle === "" || strpos($haystack, $needle) === 0;
    }

    public static function endsWith($haystack, $needle)
    {
        return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
    }

    public static function contains($haystack, $needle)
    {
        return strpos($haystack, $needle) !== false;
    }
}