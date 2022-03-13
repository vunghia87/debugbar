<?php

function event()
{
    return DebugBar\Events\Dispatcher::getInstance();
}

function debugbar()
{
    return \DebugBar\AdvanceDebugBar::getInstance();
}

//autoload modify class
function ProxyGlobalCache()
{
    require_once __DIR__ . '/Partner/GlobalCache.php';
}

function ProxyNewRelic()
{
    require_once __DIR__ . '/Partner/NewRelic.php';
}

function ProxyCommandExecution()
{
    require_once __DIR__ . '/Partner/CommandExecution.php';
}