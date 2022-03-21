<?php

//autoload modify class
function ProxyGlobalCache()
{
    include __DIR__ . '/GlobalCache.php';
}

function ProxyNewRelic()
{
    include __DIR__ . '/NewRelic.php';
}

function ProxyCommandExecution()
{
    include __DIR__ . '/CommandExecution.php';
}