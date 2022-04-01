<?php

namespace DebugBar\Loader;

class AliasLoader
{
    protected $aliases = [];
    protected $registered = false;
    private static $instance = null;

    private function __construct()
    {

    }

    /**
     * @param array $aliases
     * @return static
     */
    public static function getInstance(array $aliases = [])
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }

        $aliases = array_merge(self::$instance->getAliases(), $aliases);
        self::$instance->setAliases($aliases);
        return self::$instance;
    }

    public function load($class)
    {
        if (isset($this->aliases[$class])) {
            return class_alias($class, $this->aliases[$class]);
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