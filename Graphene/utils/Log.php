<?php
const T_PAD=256;

class Log{
    public static function setUp(){
        if(!Log::$configured){
            self::$logDir    = absolute_from_script(Settings::getInstance()->getPar('logsDir'));
            self::$debugMode = (Settings::getInstance()->getPar('debug') === true);
            if(self::$debugMode && file_exists(Log::$logDir)){rrmdir(Log::$logDir);}
            if(!file_exists(Log::$logDir)) mkdir(Log::$logDir);
            Log::$configured=true;
        }
    }

    public static function write($label, $trace = '', $object){
        self::setUp();
        $trace = Log::getTrace($trace);
        if($trace['index'] !== self::$lastTraceIndex){
            self::$lastTraceIndex = $trace['index'];
            $record = "\n\n".self::$lastTraceIndex."\n\n";
        }
        else {$record = '';}

        $record  .= ' '.str_pad($trace['line'],4).' ['.str_pad($label,5).']  '.Log::serializeObject($object)."\r\n";
        $lrecord  = str_pad('['.$trace['string'].' | '.Log::getTimeStirng().']',60).Log::serializeObject($object)."\r\n";

        $globalLog = Log::$logDir.DIRECTORY_SEPARATOR.'graphene.log';
        $labelLog  = Log::$logDir.DIRECTORY_SEPARATOR.strtolower($label).'.log';

        file_put_contents ( $globalLog, $record,  FILE_APPEND | LOCK_EX );
        file_put_contents ( $labelLog,  $lrecord, FILE_APPEND | LOCK_EX );
    }

    public static function debug($object){
        if(!Log::$debugMode)return;
        Log::write('DEBUG',debug_backtrace(),$object);
    }

    public static function err ($object){
        Log::write('ERROR',debug_backtrace(),$object);
    }

    public static function warn ($object){
        Log::write('WARNING',debug_backtrace(),$object);
    }

    public static function request ($object){
        Log::write('REQUEST',debug_backtrace(),$object);
    }

    public static function logLabel ($label,$object){
        Log::write(strtoupper($label),debug_backtrace(),$object);
    }

    public static function serializeObject($object){
        if(!is_string($object))$object = "\n--------\n".json_encode($object,JSON_PRETTY_PRINT)."\n\n-------\n";
        return $object;
    }
    public static function getTrace($backtrace){
        $exp      = explode(DIRECTORY_SEPARATOR,$backtrace[0]['file']);
        $filename = $exp[count($exp)-1];
        $time = Log::getTimeStirng();
        return [
            "file"   => $filename,
            "line"   => $backtrace[0]['line'],
            "string" => $filename.':'.$backtrace[0]['line'],
            "time"   => $time,
            "index"  => str_pad($filename,30).' | '.$time
        ];
    }
    public static function getTimeStirng(){
        $dt = new DateTime();
        return $dt->format('Y-m-d H:i:s');
    }
    private static $lastTraceIndex, $logDir, $debugMode = false, $configured = false;
}