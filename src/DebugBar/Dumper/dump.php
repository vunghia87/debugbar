<?php

use DebugBar\Dumper\Dumper;
use DebugBar\Dumper\DumperCheck;

if (!function_exists('xx')) {
    function xx($var, ...$moreVars)
    {
        $dumper = new Dumper();
        $dumper->filter(false)->onlyVar(true)->depth(2);
        if (in_array(\PHP_SAPI, ['cli', 'phpdbg'], true)) {
            $dumper->type('string');
        } else {
            $dumper->type('html');
        }
        if (!$var instanceof DumperCheck) {
            echo $dumper->dump($var);
            foreach ($moreVars as $value) {
                echo $dumper->dump($value);
            }
        } else {
            foreach ($moreVars as $value) {
                if ($var::check()) {
                    continue;
                }
                echo $dumper->dump($value);
                $var::reset();
            }
        }

    }
}

if (!function_exists('xxx')) {
    function xxx($var, ...$moreVars)
    {
        $dumper = new Dumper();
        $dumper->filter(true)->onlyVar(false)->depth(3);
        if (in_array(\PHP_SAPI, ['cli', 'phpdbg'], true)) {
            $dumper->type('string');
        } else {
            $dumper->type('html');
        }
        if (!$var instanceof DumperCheck) {
            echo $dumper->dump($var);
            foreach ($moreVars as $value) {
                echo $dumper->dump($value);
            }
        } else {
            foreach ($moreVars as $value) {
                if ($var::check()) {
                    continue;
                }
                echo $dumper->dump($value);
                $var::reset();
            }
        }
        exit(1);
    }
}

if (!function_exists('skip')) {
    function skip($skip)
    {
        return DumperCheck::skip($skip);
    }
}

if (!function_exists('when')) {
    function when($skip)
    {
        return DumperCheck::when($skip);
    }
}