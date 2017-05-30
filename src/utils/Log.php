<?php

    namespace Graphene\utils;

    const T_PAD = 256;
    use Graphene\Graphene;

    class Log {

        private static function deprecatedLogginfWarning() {
            $caller = debug_backtrace()[1];
            //Graphene::getLogger()->debug($caller);
            Graphene::getLogger($caller['class'])
                ->warn("Log class is deprecated, use Graphene::getLogger() in " . $caller['file'] . ":" . $caller['line']);
        }

        public static function err($object) {
            $caller = debug_backtrace()[1];
            self::deprecatedLogginfWarning();
            Graphene::getLogger($caller['class'])->error($object);
        }

        public static function warn($object) {
            $caller = debug_backtrace()[1];
            self::deprecatedLogginfWarning();
            Graphene::getLogger($caller['class'])->warn($object);
        }

        public static function request($object) {
            $caller = debug_backtrace()[1];
            self::deprecatedLogginfWarning();
            Graphene::getLogger($caller['class'])->info($object);
        }

        public static function info($object) {
            $caller = debug_backtrace()[1];
            self::deprecatedLogginfWarning();
            Graphene::getLogger($caller['class'])->info($object);
        }

        public static function debug($object) {
            $caller = debug_backtrace()[1];
            self::deprecatedLogginfWarning();
            Graphene::getLogger($caller['class'])->debug($object);
        }
    }