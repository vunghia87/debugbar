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

/**
 * Handler to list and open saved dataset
 */
class OpenHandler
{
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
        if (isset($request['op'])) {
            $op = $request['op'];
            if (!in_array($op, array('find', 'get', 'clear', 'all', 'detail', 'toggle'))) {
                throw new DebugBarException("Invalid operation '{$request['op']}'");
            }
        }

        if ($sendHeader && !in_array($request['op'], ['all', 'detail'])) {
            $this->debugBar->getHttpDriver()->setHeaders(array(
                'Content-Type' => 'application/json'
            ));
            $response = json_encode(call_user_func(array($this, $op), $request));
        } else {
            $response = call_user_func(array($this, $op), $request);
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
        $max = 20;
        if (isset($request['max'])) {
            $max = $request['max'];
        }

        $offset = 0;
        if (isset($request['offset'])) {
            $offset = $request['offset'];
        }

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
        include  __DIR__ . '/Viewer/all.php';
        return ob_get_clean();
    }

    public function detail($request)
    {
        $data = $this->get($request);
        xdump($data);
        ob_start();
        include  __DIR__ . '/Viewer/detail.php';
        return ob_get_clean();
    }

    public function toggle($request)
    {
        return ['result' => $this->debugBar->toggle()];
    }
}
