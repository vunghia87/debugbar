<?php

namespace DebugBar\Dumper;

class Checker
{
    protected static $instance = null;
    protected static $current = 0;
    protected static $check = 0;
    protected static $type = self::TYPE_SKIP;
    const TYPE_SKIP = 1;
    const TYPE_WHEN = 2;

    static function skip($check)
    {
        if (self::$instance == null) {
            self::$instance = new self();
            self::$check = $check;
        }

        if (is_callable($check)) {
            self::$check = $check;
        }

        return self::$instance;
    }

    static function when($check)
    {
        self::skip($check);
        self::$type = self::TYPE_WHEN;
        return self::$instance;
    }

    static function check()
    {
        $result = false;

        if (is_callable(self::$check)) {
            /** @var \Closure|\Callable $check */
            $check = self::$check;
            $result = (bool)$check();
            goto RESULT;
        }

        self::$current++;

        if (is_int(self::$check) && self::$current == self::$check) {
            $result = true;
            goto RESULT;
        }

        if (is_array(self::$check) && in_array(self::$current, (array)self::$check)) {
            $result = true;
            goto RESULT;
        }

        RESULT:
        if (self::$type == self::TYPE_SKIP) {
            return $result;
        }

        return !$result;
    }

    static function reset()
    {
        self::$instance = null;
    }
}