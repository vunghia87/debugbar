<?php

/**
 * @mixin ProxyGlobalCache
 */
class GlobalCache
{
    protected $object;

    public function __construct()
    {
        $this->object = new ProxyGlobalCache();
    }

    public function __call($method, $params)
    {
        $value = $this->object->{$method}(...$params);
        $this->setEvent($method, $params, $value);
    }

    public function __callStatic($method, $params)
    {
        $self = new self();
        $value = $self->object->{$method}(...$params);
        $self->setEvent($method, $params, $value);
    }

    protected function setEvent($method, $params, $value)
    {
        if ($method == 'get') {
            [$key] = $params;
            event()->dispatch('cache.get', ['cache.get', $key, $value]);
        }

        if ($method == 'set') {
            [$key, $value, $timelife] = $params;
            event()->dispatch('cache.set', ['cache.set', $key, $value, $timelife]);
        }
    }
}