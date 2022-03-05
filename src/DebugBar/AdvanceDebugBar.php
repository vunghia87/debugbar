<?php

namespace DebugBar;

use DebugBar\Supports\Macroable;
use Exception;

/**
 * Debug bar subclass which adds all without Request and with LaravelCollector.
 * Rest is added in Service Provider
 *
 * @method void emergency(...$message)
 * @method void alert(...$message)
 * @method void critical(...$message)
 * @method void error(...$message)
 * @method void warning(...$message)
 * @method void notice(...$message)
 * @method void info(...$message)
 * @method void debug(...$message)
 * @method void log(...$message)
 */
class AdvanceDebugBar extends DebugBar
{
    use Macroable;

    /**
     * Singleton Pattern
     * @var
     */
    private static $instance = null;

    /**
     * @var array
     */
    protected $config;

    /**
     * True when enabled, false disabled an null for still unknown
     *
     * @var bool
     */
    protected $enabled = null;

    /**
     * True when booted.
     *
     * @var bool
     */
    protected $booted = false;

    private function __construct(){}

    /**
     * @return AdvanceDebugBar|null
     */
    public static function getInstance()
    {
        if (static::$instance === null) {
            static::$instance = new static();
            static::$instance->config = require __DIR__ . '/Config/debugbar.php';
        }

        return self::$instance;
    }

    /**
     * @param array $config
     * @return $this
     */
    public function setConfig(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }

    /**
     * @return void
     */
    public final function run()
    {
        if (!$this->isEnable() || $this->inExceptArray($_REQUEST)) {
            return;
        }

        $this->boot();

        $this->modifyResponse();
    }

    /**
     * Boot the debugbar (add collectors, renderer and listener)
     */
    public function boot()
    {
        if ($this->booted) {
            return;
        }

        /** @var \DebugBar\AdvanceDebugBar $debugbar */
        $debugbar = $this;

        // Set custom error handler
        if (isset($this->config['error_handler']) && $this->config['error_handler'] == true) {
            set_exception_handler([$this, 'handleError']);
        }

        $this->selectStorage($debugbar);

        if ($this->shouldCollect('phpinfo', true)) {
            $this->addCollector(new \DebugBar\DataCollector\PhpInfoCollector());
        }

        if ($this->shouldCollect('messages', true)) {
            $shouldTrace = $this->config['options']['messages']['shouldTrace'] ?? true;
            $this->addCollector(new \DebugBar\DataCollector\MessagesCollector('messages', $shouldTrace));
        }

        if ($this->shouldCollect('time', true)) {
            $this->addCollector(new \DebugBar\DataCollector\TimeDataCollector());
        }

        if ($this->shouldCollect('memory', true)) {
            $this->addCollector(new \DebugBar\DataCollector\MemoryCollector());
        }

        if ($this->shouldCollect('exceptions', true)) {
            try {
                $exceptionCollector = new \DebugBar\DataCollector\ExceptionsCollector();
                $exceptionCollector->setChainExceptions(true);
                $this->addCollector($exceptionCollector);
            } catch (\Exception $e) {
            }
        }

        if ($this->shouldCollect('request', true)) {
            $this->addCollector(new \DebugBar\DataCollector\RequestDataCollector());
        }

        if ($this->shouldCollect('auth', false)) {
            try {
                $cacheCollector = new \DebugBar\DataCollector\AuthCollector();
                $this->addCollector($cacheCollector);
            } catch (\Exception $e) {
                $this->addThrowable(
                    new Exception('Cannot add AuthCollector to Debugbar: ' . $e->getMessage(), $e->getCode(), $e)
                );
            }
        }

        if ($this->shouldCollect('memcache', false)) {
            try {
                $shouldValue = $this->config['options']['memcache']['shouldValue'] ?? false;
                $cacheCollector = new \DebugBar\DataCollector\MemcacheCollector($shouldValue);
                $this->addCollector($cacheCollector);

                $events = \DebugBar\Events\Dispatcher::getInstance();
                $events->subscribe($cacheCollector);
            } catch (\Exception $e) {
                $this->addThrowable(
                    new Exception('Cannot add CacheCollector to Debugbar: ' . $e->getMessage(), $e->getCode(), $e)
                );
            }
        }

        $this->shouldConnectDb();

        $renderer = $this->getJavascriptRenderer();
        $renderer->setBaseUrl($this->config['assets_sites_url'] ?? '');
//        $renderer->setIncludeVendors(true);
//        $renderer->setBindAjaxHandlerToFetch(true);
//        $renderer->setBindAjaxHandlerToXHR(true);

        if ($this->getStorage()) {
            $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?openPhpDebugBar=true';
            $renderer->setOpenHandlerUrl($baseUrl);
        }

        $this->booted = true;
    }

    /**
     * @return bool
     */
    public function isEnable()
    {
        if ($this->enabled === null) {

            $this->enabled = (bool)$this->config['enabled'] ?? false;

            if (php_sapi_name() === 'cli') {
                $this->enabled = false;
            }
        }

        return $this->enabled;
    }

    /**
     * Enable the Debugbar and boot, if not already booted.
     */
    public function enable()
    {
        $this->enabled = true;

        if (!$this->booted) {
            $this->boot();
        }
    }

    /**
     * Disable the Debugbar
     */
    public function disable()
    {
        $this->enabled = false;
    }

    public function shouldCollect($name, $default = false)
    {
        return $this->config['collectors'][$name] ?? $default;
    }

    public function modifyResponse()
    {
        ob_start(function ($content) {

            if ($this->shouldCollect('response', false)) {
                $this->addCollector(new \DebugBar\DataCollector\ResponseCollector($content));
            }

            $this->sendDataInHeaders(true);

            if (stripos($content, '</body>') === false) {
                return $content;
            }

            $renderer = $this->getJavascriptRenderer();

            $head = $renderer->renderHead();
            $widget = $renderer->render();
            $replace = $head . $widget . '<script>window.onbeforeunload = function(){return true}</script>' . '</body>';
            return str_replace("</body>", $replace, $content);
        });
    }

    /**
     * @param $exception
     * @return void
     */
    public function handleError($exception)
    {
        echo $exception->getMessage() . " " . $exception->getFile() . " Line: " . $exception->getLine() . " Trace: " . $exception->getTraceAsString();

        error_log($exception->getMessage() . " " . $exception->getFile() . " Line: " . $exception->getLine() . " Trace: " . $exception->getTraceAsString());

        if ($this->hasCollector('exceptions')) {
            $collector = $this->getCollector('exceptions');
            $collector->addThrowable($exception);
        }
    }

    /**
     * Determine if the request has a URI that should be ignored.
     *
     * @param  $_REQUEST $request
     * @return bool
     */
    protected function inExceptArray(array $request)
    {
        $excepts = $this->config['except'] ?: [];
        foreach ($excepts as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            $uri = $request['REQUEST_URI'] ?? '';
            if (strpos('*', $except)) {
                return str_contains(str_replace('*', '', $except), $uri);
            }

            return $uri == $except;
        }

        return false;
    }

    /**
     * Returns a JavascriptRenderer for this instance
     *
     * @param string $baseUrl
     * @param string $basePathng
     * @return JavascriptRenderer
     */
    public function getJavascriptRenderer($baseUrl = null, $basePath = null)
    {
        if ($this->jsRenderer === null) {
            $this->jsRenderer = new JavascriptRenderer($this, $baseUrl, $basePath);
        }
        return $this->jsRenderer;
    }

    /**
     * @param string $path
     * @param array $config
     * @return mixed
     */
    protected function mergeConfigFrom(string $path, array $config = [])
    {
        return array_merge(require $path, $config);
    }

    /**
     * @param DebugBar $debugbar
     */
    protected function selectStorage(DebugBar $debugbar)
    {
        $config = $this->config;
        $enabled = $config['storage']['enabled'] ?? false;
        if (!$enabled) {
            return;
        }
        $driver = $config['storage']['driver'] ?? 'file';

        switch ($driver) {
//            case 'pdo':
//                $connection = $config->get('debugbar.storage.connection');
//                $table = $this->app['db']->getTablePrefix() . 'phpdebugbar';
//                $pdo = $this->app['db']->connection($connection)->getPdo();
//                $storage = new PdoStorage($pdo, $table);
//                break;
//            case 'redis':
//                $connection = $config->get('debugbar.storage.connection');
//                $client = $this->app['redis']->connection($connection);
//                if (is_a($client, 'Illuminate\Redis\Connections\Connection', false)) {
//                    $client = $client->client();
//                }
//                $storage = new RedisStorage($client);
//                break;
//            case 'custom':
//                $class = $config->get('debugbar.storage.provider');
//                $storage = $this->app->make($class);
//                break;
//            case 'socket':
//                $hostname = $config->get('debugbar.storage.hostname', '127.0.0.1');
//                $port = $config->get('debugbar.storage.port', 2304);
//                $storage = new SocketStorage($hostname, $port);
//                break;
            case 'file':
            default:
                $path = $config['storage']['path'] ?? sys_get_temp_dir();
                $storage = new \DebugBar\Storage\FileStorage($path);
                break;
        }
        $debugbar->setStorage($storage);

        $this->runOpen($_REQUEST);
    }

    /**
     * cheat code, need add router
     *
     * @param array $request
     * @return void
     * @throws DebugBarException
     */
    protected function runOpen(array $request)
    {
        if (!isset($request['openPhpDebugBar'])) {
            return;
        }

        $openHandler = new \DebugBar\OpenHandler($this);
        $openHandler->handle();
        exit;
    }

    /**
     * @return void
     * @throws DebugBarException
     */
    protected function shouldConnectDb()
    {
        if (empty($this->config['collectors']['db'])) {
            return;
        }

        $conn = $this->config['collectors']['db'];
        if (!$conn instanceof \PDO) {
            return;
        }

        $pdo = new \DebugBar\DataCollector\PDO\TraceablePDO($conn);
        $pdo->setFindSource(true);

        $queryCollector = new \DebugBar\DataCollector\PDO\QueryColllector($pdo);
        $queryCollector->setDataFormatter(new \DebugBar\DataFormatter\QueryFormatter());
        $queryCollector->setRenderSqlWithParams(true,"'");
        $queryCollector->setShowHints(false);
        $this->addCollector($queryCollector);
    }

    /**
     * Adds a message to the MessagesCollector
     *
     * A message can be anything from an object to a string
     *
     * @param mixed $message
     * @param string $label
     */
    public function addMessage($message, $label = 'info')
    {
        if ($this->hasCollector('messages')) {
            /** @var \DebugBar\DataCollector\MessagesCollector $collector */
            $collector = $this->getCollector('messages');
            $collector->addMessage($message, $label);
        }
    }

    /**
     * Adds an exception to be profiled in the debug bar
     *
     * @param Exception $e
     */
    public function addThrowable($e)
    {
        if ($this->hasCollector('exceptions')) {
            /** @var \DebugBar\DataCollector\ExceptionsCollector $collector */
            $collector = $this->getCollector('exceptions');
            $collector->addThrowable($e);
        }
    }

    /**
     * Magic calls for adding messages
     *
     * @param string $method
     * @param array $args
     * @return mixed|void
     */
    public function __call($method, $args)
    {
        $messageLevels = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug', 'log'];
        if (in_array($method, $messageLevels)) {
            foreach ($args as $arg) {
                $this->addMessage($arg, $method);
            }
        }
    }
}