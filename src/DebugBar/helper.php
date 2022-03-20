<?php

require __DIR__ . '/Dumper/dump.php';
require __DIR__ . '/Partner/partner.php';

if (!function_exists('event')) {
    function event()
    {
        return DebugBar\Events\Dispatcher::getInstance();
    }
}

if (!function_exists('debugbar')) {
    function debugbar()
    {
        return \DebugBar\AdvanceDebugBar::getInstance();
    }
}

if (!function_exists('info')) {
    function info(...$var)
    {
        debugbar()->info($var);
    }
}
