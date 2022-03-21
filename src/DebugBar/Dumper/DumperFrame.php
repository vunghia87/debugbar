<?php

namespace DebugBar\Dumper;

class DumperFrame
{
    protected $debugBacktrace;
    protected $backtraceExcludePaths = [];
    protected $fileContentsCache = [];
    protected $length = 60;
    protected $maxTrace = 20;
    protected static $isHeaderDumped = false;

    public function __construct(array $debugBackTrace = [])
    {
        $this->debugBacktrace = array_slice($debugBackTrace, 0, $this->maxTrace);
        $this->formatTrace();
    }

    public function mergeBacktraceExcludePaths(array $excludePaths)
    {
        $this->backtraceExcludePaths = array_merge($this->backtraceExcludePaths, $excludePaths);
    }

    protected function formatTrace()
    {
        $result = [];
        foreach ($this->debugBacktrace as $index => $trace){
            if(!isset($trace['file']) || $this->fileIsInExcludedPath($trace['file'])){
                continue;
            }

            $result[$index] = $this->parseTrace($trace);
        }

        return $this->debugBacktrace = $result;
    }

    protected function parseTrace(array $trace)
    {
        $lines = $this->getFileContents($trace['file']);
        $trace['start'] = $trace['line'] - round($this->length / 2);
        $trace['start'] = $trace['start'] < 0 ? 0 : $trace['start'];
        $trace['lines'] = array_slice($lines,  $trace['start'], $this->length);
        return $trace;
    }

    public function getFileContents(string $file)
    {
        if ($this->fileContentsCache[$file]) {
            return $this->fileContentsCache[$file];
        }
        if (file_exists($file)) {
            return $this->fileContentsCache[$file] = file($file);
        }

        return $this->fileContentsCache[$file] = array("Cannot open the file ($file) in which the exception occurred ");
    }

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

    public function getDebugBackTrace()
    {
        return $this->debugBacktrace;
    }

    public function __toString()
    {
        if (empty($this->debugBacktrace)) {
            return null;
        }

        $html = '<div class="stack"><div class="stack-nav"><ul class="stack-frames-scroll">';
        foreach ($this->debugBacktrace as $key => $value) {
            $html .= strtr(
                '<li onClick=":click" class="stack-frame :active"><span class="stack-frame-number">:index</span><div class="stack-frame-text"><span class="stack-frame-header">:file</span><span class="stack-frame-class">::class:type:function(:args)</span></div><div class="stack-frame-line"> ::line</div></li>', [
                ':click' => "show(this,{$key})",
                ':active' => $key == 0 ? 'active' : '',
                ':index' => ++$key,
                ':class' => $value['class'] ?? '',
                ':type' => $value['type'] ?? '',
                ':function' => $value['function'] ?? '',
                ':args' => $value['args'] ? implode(',', $value['args']) : '',
                ':file' => $value['file'],
                ':line' => $value['line'],
            ]);
        }
        $html .= '</ul></div><div class="stack-main"><div class="stack-main-content">';
        foreach ($this->debugBacktrace as $key => $value) {
            $code = strtr('<ol class="stack-code" start=":start">', [":start" => (int)$value['start'] + 1]);
            foreach ($value['lines'] as $index => $line) {
                $code .= strtr('<li class=":current">:line</li>', [
                    ":line" => htmlentities($line),
                    ":current" => $value['line'] - $value['start'] - 1 == $index ? 'stack-code-current' : ''
                ]);
            }
            $code .= '</ol>';

            $html .= strtr('<div class="stack-content :hidden" id=":key"><div class="stack-view"><div class="file-line">:code</div></div></div>', [
                ':hidden' => $key != 0 ? 'stack-hidden' : '',
                ':key' => "dump-{$key}",
                ':code' => $code,
            ]);
        }
        $html .= '</div></div></div>';
        return $html . $this->getHeader();
    }

    public function getHeader()
    {
        if (static::$isHeaderDumped) {
            return '';
        }

        static::$isHeaderDumped = true;

        $style = "<style>
            *{margin: 0;padding:0}
            .stack {font-family: monospace;display: grid;align-items: stretch;grid-template: calc(100vh - 1rem) / 29rem 1fr}
            .stack-nav{padding: 0;background: rgb(38 38 50);border-bottom-width: 0;border-right: 1px solid;overflow: hidden;height: 100%;border-color: #4b4b55;display: grid; grid-template: auto 1fr / 100%;position: relative;}
            .stack-frames-scroll{position: absolute;top: 0;right: 0;bottom: 0;left: 0;overflow-x: hidden;overflow-y: auto}
            .stack-frame{cursor: pointer;transition: all 0.1s ease;display: grid; align-items: flex-end;grid-template-columns: 3rem 1fr auto;border-bottom: 1px solid; border-color: #4b4b55;background-color: #262632;}
            .stack-frame.active{background-color: #3c2e60;z-index: 10;}
            .stack-frame.active .stack-frame-text { border-color: #7900F5FF;}
            .stack-frame-number{padding:0.5rem;color: #7e6ba7;font-feature-settings: 'tnum';font-variant-numeric: tabular-nums;text-align: center}
            .stack-frame-text{display: grid;align-items: center;grid-gap: 0.5rem; border-left: 2px solid;padding-left: 0.75rem;padding-top: 0.5rem;padding-bottom: 0.5rem;color: #d8d8df;border-color: #4b4b55}
            .stack-frame-header{margin-right: -20rem;width: 100%;justify-content: flex-start;align-items: baseline;display: inline-block;line-height: 1.25;word-break: break-word;}
            .stack-frame-class{display: inline-block;line-height: 1.25;color: #f0f0f58c;}
            .stack-frame-line{margin: 0.5rem 0.5rem 0.5rem 0.25rem;padding:0.2rem;text-align: right;line-height: 1.25;display: inline-block;background-color: #f0f0f50d;color: #f0f0f58c;font-size: 0.75rem}
            .stack-main{display: grid;height: 100%;overflow: hidden;background-color: #30303a;grid-template: auto 1fr / 100%;position: relative}
            .stack-main-content{overflow: hidden;}
            .stack-view{position: absolute; top: 0;right: 0;bottom: 0;left: 0;overflow: auto;background-color: #262632;font-size: 0.75rem;}
            .file-line{font: 13px monospace;line-height: 1.2em;padding: 3px 10px;text-align: left;color: #b9b5b8;margin: 0;border-bottom: 1px solid #464646;white-space: pre-wrap;word-wrap: break-word;}
            .stack-code{ margin: 0 0 0 30px}
            .stack-code li:hover{background: rgba(255, 100, 100, .07)}
            .stack-code-current{ background: rgba(255, 100, 100, .07) }
            .stack-hidden{display: none}
        </style>";

        $script = "<script>
            var frame = document.querySelectorAll('.stack-frame');
            var content = document.querySelectorAll('.stack-content');
    
            function show(event, id) {
                for (var i = 0; i < frame.length; i++) {
                    frame[i].classList.remove('active');
                    content[i].classList.add('stack-hidden');
                }
                event.classList.add('active');
                document.getElementById('dump-'+id).classList.remove('stack-hidden');
            }
        </script>";

        return $style . $script;
    }
}
