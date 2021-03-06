<?php

use DebugBar\Dumper\Dumper;
use DebugBar\Dumper\Checker;
use DebugBar\Dumper\Framer;

if (!function_exists('xdump')) {
    function xdump($var, ...$moreVars)
    {
        $dumper = (new Dumper())
            ->type(in_array(\PHP_SAPI, ['cli', 'phpdbg'], true) ? 'string' : 'html')
            ->filter(false)
            ->onlyVar(true)
            ->depth(4);
        if ($var instanceof Checker) {
            foreach ($moreVars as $value) {
                if ($var::check()) {
                    continue;
                }
                echo $dumper->dump($value);
                $var::reset();
            }
        } else {
            echo $dumper->dump($var);
            foreach ($moreVars as $value) {
                echo $dumper->dump($value);
            }
        }
    }
}

if (!function_exists('xx')) {
    function xx($var, ...$moreVars)
    {
        xdump($var, ...$moreVars);
        if (!in_array(\PHP_SAPI, ['cli', 'phpdbg'], true)) {
            echo new Framer(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS));
        }
        exit(1);
    }
}

if (!function_exists('xxx')) {
    function xxx($var, ...$moreVars)
    {
        $isCli = in_array(\PHP_SAPI, ['cli', 'phpdbg'], true);
        $dumper = (new Dumper())
            ->type($isCli ? 'string' : 'html')
            ->filter(false)
            ->onlyVar(false)
            ->depth(3);
        if ($var instanceof Checker) {
            foreach ($moreVars as $value) {
                if ($var::check()) {
                    continue;
                }
                echo $dumper->dump($value);
                $var::reset();
            }
        } else {
            echo $dumper->dump($var);
            foreach ($moreVars as $value) {
                echo $dumper->dump($value);
            }
        }
        if (!$isCli) {
            echo new Framer(debug_backtrace());
        }
        exit(1);
    }
}

if (!function_exists('xe')) {
    function xe(Throwable $e)
    {
        $ex = [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'class' => get_class($e),
            'function' => "<br /><b style='color: red'>" . $e->getMessage() ."</b>",
            'type' => "[" . $e->getCode() . "]",
            'args' => '',
        ];
        $trace = $e->getTrace();
        array_unshift($trace, $ex);
        echo new Framer($trace);
        exit(1);
    }
}

if (!function_exists('args')) {
    function args(array $vars)
    {
        $results = [];
        foreach ($vars as $var) {
            if (is_object($var)) {
                $results[] = serialize($var);
                continue;
            }
            $results[] = $var;
        }

        debugbar()->debug($results);
    }
}

if (!function_exists('skip')) {
    function skip($skip)
    {
        return Checker::skip($skip);
    }
}

if (!function_exists('when')) {
    function when($skip)
    {
        return Checker::when($skip);
    }
}