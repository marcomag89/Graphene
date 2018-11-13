<?php

namespace Graphene\utils;

use DateTime;
use DateTimeZone;

/**
 * Created by IntelliJ IDEA.
 * User: Tony
 * Date: 13/11/18
 * Time: 10:00
 */
class DateUtils
{
    /**
     * Dal formato 'yyyymmdd' a 'yyyy-mm-dd'
     * da '' a '0000-00-00'
     * @param $sData
     * @return string
     */
    public static function normalizeDate($sData)
    {
        $sRet = $sData;

        if (strlen($sData) > 10) {
            $sRet = substr($sData, 0, 10);
        }

        if (strlen($sData) == 8) {
            $sRet = substr($sData, 0, 4) . '-' . substr($sData, 4, 2) . '-' . substr($sData, 6, 2);
        }

        if (strlen($sData) < 8) {
            $sRet = '0000-00-00';
        }

        return $sRet;
    }

    /**
     * @param $data
     * @param string $locale
     * @return string
     */
    public static function UTC2Ymd($data, $locale = "Europe/Rome") {
        $dt = new DateTime($data);
        $tz = new DateTimeZone($locale);
        $dt->setTimezone($tz);
        return $dt->format('Y-m-d');
    }
}
