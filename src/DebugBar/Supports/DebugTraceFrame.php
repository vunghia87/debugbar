<?php

namespace DebugBar\Supports;

trait DebugTraceFrame
{
    protected $debugBacktrace;

    protected $backtraceExcludePaths = [
//        '/vendor/laravel/framework/src/Illuminate/Support',
//        '/vendor/laravel/framework/src/Illuminate/Database',
//        '/vendor/laravel/framework/src/Illuminate/Events',
    ];
    
    /**
     * @param $debugBackTrace
     * @return void
     */
    public function debugTrace($debugBackTrace = [])
    {
        $this->debugBacktrace = $debugBackTrace;
    }

    /**
     * @return array
     */
    public function getDebugTrace()
    {
        $stack = $this->debugBacktrace;

        $sources = [];

        foreach ($stack as $index => $trace) {
            $sources[] = $this->parseTrace($index, $trace);
        }

        return array_filter($sources);
    }

    /**
     * Set additional paths to exclude from the backtrace
     *
     * @param array $excludePaths Array of file paths to exclude from backtrace
     */
    public function mergeBacktraceExcludePaths(array $excludePaths)
    {
        $this->backtraceExcludePaths = array_merge($this->backtraceExcludePaths, $excludePaths);
    }

    /**
     * Parse a trace element from the backtrace stack.
     *
     * @param  int    $index
     * @param  array  $trace
     * @return object|bool
     */
    protected function parseTrace($index, array $trace)
    {
        $frame = (object) [
            'index' => $index,
            'namespace' => null,
            'name' => null,
            'line' => isset($trace['line']) ? $trace['line'] : '?',
        ];

        if (
            isset($trace['class']) &&
            isset($trace['file']) &&
            !$this->fileIsInExcludedPath($trace['file'])
        ) {
            $file = $trace['file'];

            $frame->name = $this->normalizeFilename($file);

            return $frame;
        }

        return false;
    }

    /**
     * Check if the given file is to be excluded from analysis
     *
     * @param string $file
     * @return bool
     */
    protected function fileIsInExcludedPath($file)
    {
        $normalizedPath = str_replace('\\', '/', $file);

        foreach ($this->backtraceExcludePaths as $excludedPath) {
            if (strpos($normalizedPath, $excludedPath) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Shorten the path by removing the relative links and base dir
     *
     * @param string $path
     * @return string
     */
    protected function normalizeFilename($path)
    {
        if (file_exists($path)) {
            $path = realpath($path);
        }

        return str_replace( $this->getHtmlRootFolder(), '', $path);
    }

    /**
     * @param string $root
     * @return array|string|string[]|null
     */
    protected function getHtmlRootFolder(string $root = '/var/www/') {
        $ret = str_replace(' ', '', $_SERVER['DOCUMENT_ROOT']);
        $ret = rtrim($ret, '/') . '/';

        // -- if doesn't contain root path, find using this file's loc. path --
        if (!preg_match("#".$root."#", $ret)) {
            $root = rtrim($root, '/') . '/';
            $root_arr = explode("/", $root);
            $pwd_arr = explode("/", getcwd());
            $ret = $root . $pwd_arr[count($root_arr) - 1];
        }

        $dir = (preg_match("#".$root."#", $ret)) ? rtrim($ret, '/') . '/' : null;
        return str_replace('public_html/', '', $dir);
    }
}