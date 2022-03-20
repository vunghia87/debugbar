<?php

namespace DebugBar\Supports;

trait DebugTraceFrame
{
    protected $debugBacktrace;

    protected $backtraceExcludePaths = [];

    protected $editors = [
        'sublime' => 'subl://open?url=file://%file&line=%line',
        'textmate' => 'txmt://open?url=file://%file&line=%line',
        'emacs' => 'emacs://open?url=file://%file&line=%line',
        'macvim' => 'mvim://open/?url=file://%file&line=%line',
        'phpstorm' => 'phpstorm://open?file=%file&line=%line',
        'idea' => 'idea://open?file=%file&line=%line',
        'vscode' => 'vscode://file/%file:%line',
        'vscode-insiders' => 'vscode-insiders://file/%file:%line',
        'vscode-remote' => 'vscode://vscode-remote/%file:%line',
        'vscode-insiders-remote' => 'vscode-insiders://vscode-remote/%file:%line',
        'vscodium' => 'vscodium://file/%file:%line',
        'nova' => 'nova://core/open/file?filename=%file&line=%line',
        'xdebug' => 'xdebug://%file@%line',
        'atom' => 'atom://core/open/file?filename=%file&line=%line',
        'espresso' => 'x-espresso://open?filepath=%file&lines=%line',
        'netbeans' => 'netbeans://open/?f=%file:%line',
    ];

    protected $editor = 'phpstorm';

    /**
     * @param array $debugBackTrace
     * @return void
     */
    public function debugTrace(array $debugBackTrace = [])
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
     * @param int $index
     * @param array $trace
     * @return object|bool
     */
    public function parseTrace(int $index, array $trace)
    {
        $frame = (object)[
            'index' => $index,
            'namespace' => null,
            'name' => null,
            'line' => $trace['line'] ?? '?',
        ];

        if (isset($trace['file']) && !$this->fileIsInExcludedPath($trace['file'])) {
            $file = $trace['file'];
            $frame->name = $file;
            //instance debugBar
            $config = debugbar()->getConfig();
            if (!isset($config['remote_sites_path']) && !isset($config['local_sites_path'])) {
                return $frame;
            }
            $filePath = trim(str_replace(trim($config['remote_sites_path'], '/'), '', $file), '/');
            $folderPath = trim($config['local_sites_path'], '\\') . '\\' . str_replace('/', '\\', $filePath);
            $frame->editorHref = str_replace(['%file', '%line'], [$folderPath, $frame->line], $this->editors[$this->editor]);
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
}