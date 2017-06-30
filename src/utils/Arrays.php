<?php

    /**
     * Created by IntelliJ IDEA.
     * User: marco
     * Date: 16/06/17
     * Time: 19:42
     */

    namespace Graphene\utils;

    class Arrays {
        static function flatten($array,$prefix = '') {
            $result = [];
            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    $result = $result + self::flatten($value,$prefix . $key . '.');
                } else {
                    $result[$prefix . $key] = $value;
                }
            }

            return $result;
        }

        static function unflatten($array,$prefix = '') {
            $result = [];
            foreach ($array as $key => $value) {
                if (!empty($prefix)) {
                    $key = preg_replace('#^' . preg_quote($prefix) . '#','',$key);
                }
                if (strpos($key,'.') !== false) {
                    parse_str('result[' . str_replace('.','][',$key) . "]=" . $value);
                } else {
                    $result[$key] = $value;
                }
            }

            return $result;
        }


        static function valueDiff($current,$new,$flatResult = true) {
            $created = [];
            $updated = [];
            $deleted = [];
            $vArr1 = self::flatten($current);
            $vArr2 = self::flatten($new);

            foreach ($vArr2 as $k2 => $v2) {
                if (!array_key_exists($k2,$vArr1)) {
                    $created[$k2] = $v2;
                } else if ($vArr1[$k2] != $v2) {
                    $updated[$k2] = $v2;
                }
            }

            foreach ($vArr1 as $k1 => $v1) {
                if (!array_key_exists($k1,$vArr2)) {
                    $deleted[$k1] = $v1;
                }
            }


            $ret = [
                'created' => $flatResult ? $created : self::unflatten($created),
                'updated' => $flatResult ? $updated : self::unflatten($updated),
                'deleted' => $flatResult ? $deleted : self::unflatten($deleted),
            ];

            return $ret;

        }
    }