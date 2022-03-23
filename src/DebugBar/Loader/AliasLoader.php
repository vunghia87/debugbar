<?php

namespace DebugBar\Loader;

class AliasLoader
{
    protected $aliases = [
        \DebugBar\Loader\ProxyGlobalCache::class => \GlobalCache::class,
        \DebugBar\Loader\ProxyNewRelic::class => \NewRelic::class,
        \DebugBar\Loader\ProxyCommandExecution::class => \BeSmartee\Utils\CommandExecution::class,
    ];
    protected $registered = false;
    protected static $instance;

    private function __construct($aliases)
    {
        $this->aliases = $aliases;
    }

    /**
     * @param array $aliases
     * @return static
     */
    public static function getInstance(array $aliases = [])
    {
        if (is_null(static::$instance)) {
            return static::$instance = new static($aliases);
        }

        $aliases = array_merge(static::$instance->getAliases(), $aliases);

        static::$instance->setAliases($aliases);

        return static::$instance;
    }

    public function load($alias)
    {
        if (isset($this->aliases[$alias])) {
            return class_alias($this->aliases[$alias], $alias);
        }
    }

    public function boot()
    {
        foreach ($this->aliases as $alias) {
            $this->load($alias);
        }
    }

    public function alias($alias, $class)
    {
        $this->aliases[$alias] = $class;
    }

    protected function prependToLoaderStack()
    {
        spl_autoload_register([$this, 'load'], true, true);
    }

    public function setAliases(array $aliases)
    {
        $this->aliases = $aliases;
    }

    public function getAliases()
    {
        return $this->aliases;
    }

    public function register()
    {
        if (! $this->registered) {
            $this->prependToLoaderStack();

            $this->registered = true;
        }
    }

    public function isRegistered()
    {
        return $this->registered;
    }

    public function setRegistered($value)
    {
        $this->registered = $value;
    }

    public static function setInstance($loader)
    {
        static::$instance = $loader;
    }

    private function __clone()
    {
        //
    }
}