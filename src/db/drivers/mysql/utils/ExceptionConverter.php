<?php
namespace Graphene\db\drivers\mysql\utils;
use Graphene\controllers\exceptions\GraphException;
use \Log;

class ExceptionConverter {
    public static function throwException($mysqlErr){
        switch ($mysqlErr[1]){
            case 1062: {self::parseUniqueException($mysqlErr);}
            default  : throw new GraphException('mySql exception: ' . $mysqlErr[2],$mysqlErr[1],500);

        }
    }
    private static function parseUniqueException($mysqlErr){
        // Duplicate entry '(.*)' for key '(\w)*_UNIQUE'
        preg_match("/'(\\w)*'$/", $mysqlErr[2], $matches, PREG_OFFSET_CAPTURE, 3);
        $field=explode('_',$matches[0][0])[0];
        $field=ltrim($field,'\'');
        throw new GraphException('field \''.$field.'\' must be unique',400,400);
    }
}