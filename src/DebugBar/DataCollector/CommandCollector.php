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

use DebugBar\Events\Dispatcher;
use DebugBar\Supports\DebugTraceFrame;

/**
 * Collects [class, params] of command
 */
class CommandCollector extends DataCollector implements Renderable, AssetProvider
{
    use DebugTraceFrame;

    protected $classMap = [
        'command' => 'command',
    ];

    protected $data = [];

    /**
     * @return array
     */
    public function collect()
    {
        return [
            'count' => count($this->data),
            'commands' => $this->data
        ];
    }

    /**
     * @return void
     */
    public function onCommandEvent()
    {
        [$command, $options, $arguments, $callable, $params] = func_get_args();

        $item['command'] = $command;
        $item['options'] = $options;
        $item['arguments'] = $arguments;

        if (!empty($callable)) {
            $item['callable'] = $callable;
        }

        if (!empty($params)) {
            $item['params'] = $params;
        }

        $item['time'] = date('Y-m-d H:i:s');
        $this->debugBacktrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT, 6);
        $this->debugBacktrace = array_slice( $this->debugBacktrace, 2);
        $item['backtrace'] = $this->getDebugTrace();

        $this->data[] = $item;
    }

    /**
     * @param Dispatcher $dispatcher
     * @return void
     */
    public function subscribe(Dispatcher $dispatcher)
    {
        foreach ($this->classMap as $eventClass => $type) {
            $dispatcher->listen($eventClass, [$this, 'onCommandEvent']);
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'command';
    }

    /**
     * {@inheritDoc}
     */
    public function getWidgets()
    {
        return array(
            "command" => array(
                "icon" => "terminal",
                "widget" => "PhpDebugBar.Widgets.CommandWidget",
                "map" => "command.commands",
                "default" => "{}"
            ),
            "command:badge" => array(
                "map" => "command.count",
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
            'js' => 'widgets/commands/widget.js'
        );
    }
}
