<?php
const T_PAD=256;

class Log{
    public static function setUp(){
        if(!Log::$configured){
            $logSettings     = Settings::getInstance()->getPar('log');
            self::$debugMode = Settings::getInstance()->getPar('debug') === true;
            if($logSettings != null){
                self::$configured = true;
                self::$err   = absolute_from_script($logSettings ['errors']);
                self::$warn  = absolute_from_script($logSettings ['warnings']);
                self::$all   = absolute_from_script($logSettings ['all']);
                self::$debug = absolute_from_script($logSettings ['debug']);
                self::$req   = absolute_from_script($logSettings ['requests']);
                //removing old files
                if(self::$debugMode){
                    if(file_exists(Log::$err))   unlink(Log::$err);
                    if(file_exists(Log::$warn))  unlink(Log::$warn);
                    if(file_exists(Log::$all))   unlink(Log::$all);
                    if(file_exists(Log::$debug)) unlink(Log::$debug);
                    if(file_exists(Log::$req))   unlink(Log::$req);
                }
            }
        }
    }

    public static function write($label, $object, $traceStr = ''){
        self::setUp();
        if($traceStr === ''){$traceStr = Log::getTraceString(debug_backtrace());}
        $record   = '['.str_pad($label,5).']   '.str_pad($object,T_PAD).' # '.Log::getTimeStirng().' | '.$traceStr."\n";
        //$record   = str_pad('['.$label.' | '.Log::getTimeStirng(). ' | '.$traceStr,60).'] '.$object."\n";
        file_put_contents(Log::$all, $record, FILE_APPEND | LOCK_EX);
    }

    public static function debug($object){
        if(!self::$debugMode)return;
        self::setUp();
        $traceStr = Log::getTraceString(debug_backtrace());
        $record   = str_pad($object,T_PAD).' # '.Log::getTimeStirng().' | '.$traceStr."\n";
        file_put_contents(Log::$debug, $record, FILE_APPEND | LOCK_EX);
        Log::write('DEBUG',$object,$traceStr);
    }

    public static function err ($object){
        self::setUp();
        $traceStr = Log::getTraceString(debug_backtrace());
        $record   = str_pad($object,T_PAD).' # '.Log::getTimeStirng().' | '.$traceStr."\n";
        file_put_contents(Log::$err, $record, FILE_APPEND | LOCK_EX);
        Log::write('ERROR',$object,$traceStr);
    }

    public static function warn ($object){
        self::setUp();
        $traceStr = Log::getTraceString(debug_backtrace());
        $record   = str_pad($object,T_PAD).' # '.Log::getTimeStirng().' | '.$traceStr."\n";
        file_put_contents(Log::$warn, $record, FILE_APPEND | LOCK_EX);
        Log::write('WARNING',$object,$traceStr);
    }

    public static function request ($object){
        self::setUp();
        $traceStr = Log::getTraceString(debug_backtrace());
        $record   = str_pad($object,T_PAD).' # '.Log::getTimeStirng().' | '.$traceStr."\n";
        file_put_contents(Log::$req, $record, FILE_APPEND | LOCK_EX);
        Log::write('REQUEST',$object,$traceStr);
    }

    public static function getTraceString($backtrace){
        $exp = explode('/',$backtrace[0]['file']);
        $filename = $exp[count($exp)-1];
        return $filename.':'.$backtrace[0]['line'];
    }

    public static function getTimeStirng(){
        $dt = new DateTime();
        return $dt->format('Y-m-d H:i:s');
    }
    private static $err, $all, $debug, $req, $warn, $debugMode = false, $configured = false;
}