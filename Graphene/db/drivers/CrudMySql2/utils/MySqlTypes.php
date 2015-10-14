<?php
namespace Graphene\db\drivers;
use Graphene\models\Model;

class MySqlTypes
{
    public static function convertFlatStructTypes($flatTypes){
        $flatConverted=[];
        foreach($flatTypes as $key=>$ftype){
            $flatConverted[$key]=self::convertTypes($ftype);
        }
        return $flatConverted;
    }

    public static function convertTypes($types){
        $texpl = explode(Model::CHECK_SEP, $types);
        var_dump($types);
        unset($texpl[0]);
        array_values($texpl);
        $ret = '';
        foreach ($texpl as $t) {
            $test = Model::CHECK_SEP . explode(Model::CHECK_PAR, $t)[0];
            if (preg_match('/' . Model::CHECK_PAR . '/', $types))  $test .= Model::CHECK_PAR;
            switch ($test) {
                /* Type checkers */
                case Model::BOOLEAN:  {$ret = ' INT(1)';      break; }
                case Model::DECIMAL:  {$ret = ' DOUBLE';      break; }
                case Model::INTEGER:  {$ret = ' INT(11)';     break; }
                case Model::STRING:   {$ret = ' VARCHAR(45)'; break; }
                case Model::UID:      {$ret = ' VARCHAR(11)'; break; }
                case Model::DATE:     {$ret = ' DATE';        break; }
                case Model::DATETIME: {$ret = ' DATETIME';    break; }
                case Model::MATCH:    {$ret = ' VARCHAR(45)'; break; }
                case Model::MAX_LEN:  {$ret = ' VARCHAR(' . explode(Model::CHECK_PAR, $t)[1] . ')';        break; }
                case Model::MIN_LEN:  {$ret = ' VARCHAR(' . (45 + explode(Model::CHECK_PAR, $t)[1]) . ')'; break; }
                case Model::ENUM:     {
                    $elems = explode(',', explode(Model::CHECK_PAR, $t)[1]);
                    $tp = ' ENUM(';
                    foreach ($elems as $elem) {
                        $tp = $tp . '\'' . $elem . '\',';
                    }
                    $tp  = substr($tp, 0, - 1);
                    $tp  = $tp . ')';
                    $ret = $tp;
                    break;
                }
                case Model::NOT_NULL: {$ret = $ret . ' NOT NULL';                                          break; }
                /* Content checkers */
                default: {}
            }
        }
        if (strcmp($ret, '') == 0) $ret = ' VARCHAR(200)';
        return $ret;
    }
}