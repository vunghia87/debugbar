<?php

function event()
{
    return DebugBar\Events\Dispatcher::getInstance();
}

function ProxyGlobalCache()
{
    require_once __DIR__ . '/Partner/GlobalCache.php';
}
