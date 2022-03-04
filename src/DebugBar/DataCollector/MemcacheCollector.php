<?php
/*
 * This file is part of the DebugBar package.
 *
 * (c) 2013 Maxime Bouroumeau-Fuseau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DebugBar\DataCollector;

use DebugBar\Events\Cache\CacheEvent;
use DebugBar\Events\Cache\CacheSet;
use DebugBar\Events\Dispatcher;
use DebugBar\Supports\DebugTraceFrame;

/**
 * Collects [key, value] of cache
 */
class MemcacheCollector extends DataCollector implements Renderable, AssetProvider
{
    use DebugTraceFrame;

    protected $shouldValue = false;

    public function __construct(bool $shouldValue)
    {
        $this->shouldValue = $shouldValue;
    }

    protected $classMap = [
        'cache.set' => 'set',
        'cache.get' => 'get'
    ];

    protected $data = [];

    /**
     * @return array
     */
    public function collect()
    {
        return [
            'count' => count($this->data),
            'memcaches' => $this->data
        ];
    }

    /**
     * @return void
     */
    public function onCacheEvent()
    {
        [$label, $key, $value, $timelife] = func_get_args();

        $item['label'] = $label;
        $item['key'] = $key;
        if ($this->shouldValue) {
            $item['value'] = $this->parseValue($value);
        }

        if (!empty($timelife)) {
            $item['timelife'] = $timelife;
        }
        $item['time'] = date('Y-m-d H:i:s');
        $this->debugBacktrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT, 7);
        $this->debugBacktrace = array_slice( $this->debugBacktrace, 3);
        $item['backtrace'] = $this->getDebugTrace();

        $this->data[] = $item;
    }

    /**
     * @param mixed $value
     * @return void
     */
    protected function parseValue($value)
    {
        if (is_object($value)) {
            return get_class($value);
        }

        return $this->getDataFormatter()->formatVar($value);
    }

    /**
     * @param Dispatcher $dispatcher
     * @return void
     */
    public function subscribe(Dispatcher $dispatcher)
    {
        foreach ($this->classMap as $eventClass => $type) {
            $dispatcher->listen($eventClass, [$this, 'onCacheEvent']);
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'memcache';
    }

    /**
     * {@inheritDoc}
     */
    public function getWidgets()
    {
        return array(
            "memcache" => array(
                "icon" => "clipboard",
                "widget" => "PhpDebugBar.Widgets.MemcacheWidget",
                "map" => "memcache.memcaches",
                "default" => "{}"
            ),
            "memcache:badge" => array(
                "map" => "memcache.count",
                "default" => 0
            )
        );
    }

    /**
     * @return array
     */
    public function getAssets()
    {
        return array(
            'css' => 'widgets/sqlqueries/widget.css',
            'js' => 'widgets/memcaches/widget.js'
        );
    }
}
