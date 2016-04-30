<?php
const T_PAD = 256;
use Graphene\models\Model;

class Log {
    private static $lastTraceIndex, $logDir, $debugMode = false, $configured = false;

    public static function debug($object) {
        if (!Log::$debugMode) {
            return;
        }
        Log::write('DEBUG', debug_backtrace(), $object);
    }

    public static function write($label, $trace = '', $object) {
        self::setUp();
        $trace = Log::getTrace($trace);

        /*        if ($trace['index'] !== self::$lastTraceIndex) {
                    self::$lastTraceIndex = $trace['index'];
                    $record = "\n\n" . self::$lastTraceIndex . "\n\n";
                } else {
                    $record = '';
                }*/

        $record = $trace["time"] . " " . str_pad($label, 8) . Log::serializeObject($object) . " @" . $trace['file'] . ":" . $trace['line'] . "\r\n";
        //$lrecord = str_pad('[' . $trace['string'] . ' | ' . Log::getTimeStirng() . ']', 60) . Log::serializeObject
        //($object) . "\r\n";
        //$clog = $label . Log::serializeObject($object) . ' @ ' . $trace['file'] . ':' . $trace['line'];

        $globalLog = Log::$logDir . DIRECTORY_SEPARATOR . 'graphene.log';
        $labelLog = Log::$logDir . DIRECTORY_SEPARATOR . strtolower($label) . '.log';

        file_put_contents($globalLog, $record, FILE_APPEND | LOCK_EX);
        //file_put_contents($labelLog, $lrecord, FILE_APPEND | LOCK_EX);
        //error_log($clog);
    }

    public static function setUp() {
        if (!Log::$configured) {
            self::$logDir = absolute_from_script(Settings::getInstance()->getPar('logsDir'));
            self::$debugMode = (Settings::getInstance()->getPar('debug') === true);
            if (self::$debugMode && file_exists(Log::$logDir)) {
                rrmdir(Log::$logDir);
            }
            if (!file_exists(Log::$logDir)) {
                mkdir(Log::$logDir);
            }
            Log::$configured = true;
        }
    }

    public static function getTrace($backtrace) {
        $exp = explode(DIRECTORY_SEPARATOR, $backtrace[0]['file']);
        $filename = $exp[count($exp) - 1];
        $time = Log::getTimeStirng();

        return [
            "file"  => $filename,
            "line"  => $backtrace[0]['line'],
            "time"  => $time,
            "stack" => json_encode($backtrace, JSON_PRETTY_PRINT)
        ];
    }

    public static function getTimeStirng() {
        $dt = new DateTime();

        return $dt->format('Y-m-d H:i:s');
    }

    public static function serializeObject($object) {
        $toString = "";
        if ($object instanceof Model) {
            $object = [
                $object->getModelName() => $object->getContent()
            ];
        }

        if (is_object($object) || is_array($object)) {
            $toString = "\n--------\n" . json_encode($object, JSON_PRETTY_PRINT) . "\n\n-------\n";
        } else {
            $toString = $object;
        }

        return $toString;
    }

    public static function err($object) {
        Log::write('ERROR', debug_backtrace(), $object);
    }

    public static function warn($object) {
        Log::write('WARNING', debug_backtrace(), $object);
    }

    public static function request($object) {
        Log::write('REQUEST', debug_backtrace(), $object);
    }

    public static function info($object) {
        Log::write('INFO', debug_backtrace(), $object);
    }

    public static function logLabel($label, $object) {
        Log::write(strtoupper($label), debug_backtrace(), $object);
    }
}