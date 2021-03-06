<?php

namespace DebugBar;

use DebugBar\Loader\AliasLoader;
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
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->config = require __DIR__ . '/Config/debugbar.php';
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
        /** @var \DebugBar\AdvanceDebugBar $debugbar */
        $debugbar = $this;

        $this->selectStorage($debugbar);

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

        $this->loader();

        $events = \DebugBar\Events\Dispatcher::getInstance();

        // Set custom error handler
        if (isset($this->config['error_handler']) && $this->config['error_handler'] == true) {
            set_exception_handler([$this, 'handleError']);
        }

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
            $shouldServer = $this->config['options']['request']['shouldServer'] ?? false;
            $this->addCollector(new \DebugBar\DataCollector\RequestDataCollector($shouldServer));
        }

        if ($this->shouldCollect('auth', false)) {
            try {
                $cacheCollector = new \DebugBar\DataCollector\AuthCollector();
                $this->addCollector($cacheCollector);
            } catch (\Exception $e) {
                $this->addThrowable(
                    new Exception('Cannot add AuthCollector to DebugBar: ' . $e->getMessage(), $e->getCode(), $e)
                );
            }
        }

        if ($this->shouldCollect('memcache', false)) {
            try {
                $shouldValue = $this->config['options']['memcache']['shouldValue'] ?? false;
                $cacheCollector = new \DebugBar\DataCollector\MemcacheCollector($shouldValue);
                $this->addCollector($cacheCollector);
                $events->subscribe($cacheCollector);
            } catch (\Exception $e) {
                $this->addThrowable(
                    new Exception('Cannot add CacheCollector to DebugBar: ' . $e->getMessage(), $e->getCode(), $e)
                );
            }
        }

        if ($this->shouldCollect('command', false)) {
            try {
                $commandCollector = new \DebugBar\DataCollector\CommandCollector();
                $this->addCollector($commandCollector);
                $events->subscribe($commandCollector);
            } catch (\Exception $e) {
                $this->addThrowable(
                    new Exception('Cannot add CommandCollector to DebugBar: ' . $e->getMessage(), $e->getCode(), $e)
                );
            }
        }

        $this->shouldConnectDb();

        $renderer = $this->getJavascriptRenderer();
        $renderer->setBaseUrl($this->getAssetPath());
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
            $isEnable = (bool)$this->config['enabled'] ?? false;
            $this->enabled = $isEnable && $this->isEnableByCache();
        }

        return $this->enabled ;
    }

    /**
     * @return string
     */
    protected function pathFileCache()
    {
        return sys_get_temp_dir() . '/debugBarCache.txt';
    }

    /**
     * @return bool
     */
    protected function isEnableByCache()
    {
        $fileCache = $this->pathFileCache();
        if (!file_exists($fileCache)) {
            file_put_contents($fileCache, 0);
        }
        return file_get_contents($fileCache) == 1;
    }

    /**
     * @return bool
     */
    public function toggleCache()
    {
        $fileCache = $this->pathFileCache();
        $content = file_get_contents($fileCache);
        file_put_contents($fileCache, !$content);
        return $content;
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

    /**
     * @return bool
     */
    public function watchable()
    {
        return isset($this->config['watchable']) && $this->config['watchable'] == 1;
    }

    /**
     * @return bool
     */
    public function monitorable()
    {
        return !empty(array_filter($this->getMonitor()));
    }

    /**
     * @return array
     */
    public function getMonitor()
    {
        return $this->config['monitors'] ?? [];
    }

    public function shouldCollect($name, $default = false)
    {
        return $this->config['collectors'][$name] ?? $default;
    }

    public function modifyResponse()
    {
        if ($this->shouldCollect('xdebug_trace', false) && extension_loaded('xdebug')) {
            xdebug_start_trace(null,4);
            $this->addCollector(new \DebugBar\DataCollector\XdebugTraceCollector());
        }

        ob_start(function ($content) {
            if ($this->shouldCollect('response', false)) {
                $this->addCollector(new \DebugBar\DataCollector\ResponseCollector($content));
            }

            if ($this->watchable()) {
                $this->collect();
                return $content;
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

        error_log($exception);
        error_log(sprintf("context:[GET] %s [POST] %s"), json_encode($_GET ?? ''), json_encode($_POST ?? ''));

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
                return strpos(str_replace('*', '', $except), $uri);
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
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
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

        $skip = $this->config['options']['db']['skip'] ?? [];
        $listen = $this->config['options']['db']['listen'] ?? [];
        $queryCollector = new \DebugBar\DataCollector\PDO\QueryCollector($pdo, null, $skip, $listen);
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
     * @throws Exception
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
     * @throws Exception
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
     * @return void
     */
    protected function loader()
    {
        $loader = AliasLoader::getInstance([
            \DebugBar\Loader\ProxyGlobalCache::class => \GlobalCache::class,
            \DebugBar\Loader\ProxyNewRelic::class => \NewRelic::class,
            \DebugBar\Loader\ProxyCommandExecution::class => \BeSmartee\Utils\CommandExecution::class,
        ]);

        foreach ($loader->getAliases() as $class => $alias) {
            $loader->load($class);
        }
    }

    /**
     * @return mixed|string
     */
    public function getAssetPath()
    {
        return $this->config['assets_sites_url'] ?? '';
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