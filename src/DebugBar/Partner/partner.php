<?php

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