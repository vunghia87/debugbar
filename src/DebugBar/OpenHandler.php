<?php
/*
 * This file is part of the DebugBar package.
 *
 * (c) 2013 Maxime Bouroumeau-Fuseau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DebugBar;

use DebugBar\Dumper\Framer;

/**
 * Handler to list and open saved dataset
 */
class OpenHandler
{
    /**
     * @var AdvanceDebugBar|DebugBar
     */
    protected $debugBar;

    /**
     * @param DebugBar|AdvanceDebugBar $debugBar
     * @throws DebugBarException
     */
    public function __construct(DebugBar $debugBar)
    {
        if (!$debugBar->isDataPersisted()) {
            throw new DebugBarException("DebugBar must have a storage backend to use OpenHandler");
        }
        $this->debugBar = $debugBar;
    }

    /**
     * Handles the current request
     *
     * @param array $request Request data
     * @param bool $echo
     * @param bool $sendHeader
     * @return string
     * @throws DebugBarException
     */
    public function handle($request = null, $echo = true, $sendHeader = true)
    {
        if ($request === null) {
            $request = $_REQUEST;
        }

        $op = 'find';
        $opView = ['all', 'detail', 'frame', 'monitor'];
        if (isset($request['op'])) {
            $op = $request['op'];
            if (!in_array($op, array_merge($opView, ['find', 'get', 'clear', 'toggle']))) {
                throw new DebugBarException("Invalid operation '{$request['op']}'");
            }
        }

        $response = call_user_func(array($this, $op), $request);

        if ($sendHeader && !in_array($request['op'], $opView)) {
            $this->debugBar->getHttpDriver()->setHeaders(array(
                'Content-Type' => 'application/json'
            ));
            $response = json_encode($response);
        }

        if ($echo) {
            echo $response;
        }
        return $response;
    }

    /**
     * Find operation
     * @param $request
     * @return array
     */
    protected function find($request)
    {
        $max = $request['max'] ?? 20;
        $offset = $request['offset'] ?? 0;

        return $this->debugBar->getStorage()->find($request, $max, $offset);
    }

    /**
     * Get operation
     * @param $request
     * @return array
     * @throws DebugBarException
     */
    protected function get($request)
    {
        if (!isset($request['id'])) {
            throw new DebugBarException("Missing 'id' parameter in 'get' operation");
        }
        return $this->debugBar->getStorage()->get($request['id']);
    }

    /**
     * Clear operation
     */
    protected function clear($request)
    {
        $this->debugBar->getStorage()->clear();
        return array('success' => true);
    }

    protected function all($request)
    {
        $request['type'] = $request['type'] ?? 'request';
        $request['max'] = $request['max'] ?? 50;
        $data = $this->find($request);
        ob_start();
        include __DIR__ . '/Viewer/all.php';
        return ob_get_clean();
    }

    protected function detail($request)
    {
        $data = $this->get($request);
        ob_start();
        include __DIR__ . '/Viewer/detail.php';
        return ob_get_clean();
    }

    protected function toggle($request)
    {
        return ['result' => $this->debugBar->toggle()];
    }

    protected function frame($request)
    {
        if (!$request['type']) {
            return '';
        }
        $data = $this->get($request);
        $index = $request['index'] ?? 0;

        $style = 'background-color:#262632;font: 13px monospace;line-height:1.2em;padding:3px 10px;text-align:left;color:#b9b5b8;margin:0;white-space:pre-wrap;word-wrap: break-word';
        switch ($request['type']) {
            case 'pdo':
                $dataType = $data['pdo']['statements'][$index] ?? [];
                echo $dataType['sql'] ? "<pre style='$style'>$dataType[sql]</pre><hr>" : '';
                break;
            case 'memcache':
                $dataType = $data['memcache']['memcaches'][$index] ?? [];
                echo $dataType['value'] ? "$dataType[value]<hr>" : '';
                break;
            case 'message':
                $dataType = $data['messages']['messages'][$index] ?? [];
                echo $dataType['message_html'] ? "$dataType[message_html]<hr>" : '';
                break;
            case 'command':
                $dataType = $data['command']['commands'][$index] ?? [];

                echo $dataType['arguments'] ? "<pre style='$style'>$dataType[arguments]</pre><hr>" : '';
                echo $dataType['options'] ? "<pre style='$style'>$dataType[options]</pre><hr>" : '';
                break;
        }

        if (!isset($dataType['backtrace'])) {
            return '';
        }

        //xdump($dataType);
        return new Framer($dataType['backtrace'] ?? []);
    }

    protected function monitor($request)
    {
        $request['type'] = 'monitor';
        $request['max'] = $request['max'] ?? 50;
        $request['monitors'] = $this->debugBar->getMonitor();
        $data = $this->find($request);
        ob_start();
        include __DIR__ . '/Viewer/all.php';
        return ob_get_clean();
    }
}
