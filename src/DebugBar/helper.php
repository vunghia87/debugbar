<?php

function event()
{
    return DebugBar\Events\Dispatcher::getInstance();
}

function debugbar()
{
    return \DebugBar\AdvanceDebugBar::getInstance();
}

function ProxyGlobalCache()
{
    require_once __DIR__ . '/Partner/GlobalCache.php';
}
