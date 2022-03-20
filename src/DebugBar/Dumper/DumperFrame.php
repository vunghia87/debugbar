<?php

namespace DebugBar\Dumper;

class DumperFrame
{
    protected $debugBacktrace;

    protected $backtraceExcludePaths = [];

    protected $fileContentsCache = [];

    protected $length = 30;

    protected $maxTrace = 20;

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
//        if (empty($this->debugBacktrace)) {
//            return null;
//        }

        $style = "<style>
            *{margin: 0;padding:0}
            .trace {font-family: monospace;border-collapse: collapse;width: 100%;margin:0;height: 100%;background:#322931 }
            .trace-left {width: 32%;text-overflow: ellipsis;padding: 0;list-style: none;background: #eee;float: left;overflow: hidden;height: 100%;}
            .trace-left li {padding: 10px;cursor: pointer;transition: all 0.1s ease;background: #eeeeee;border-bottom: 1px solid rgba(0, 0, 0, .05)}
            .trace-left li.active {box-shadow: inset -5px 0 0 0 #4288CE;color: #4288CE}
            .trace-index{ font-size: 11px;height: 18px;width: 18px;line-height: 18px;border-radius: 5px;padding: 0 1px 0 1px;text-align: center;display: inline-block;background-color: #2a2a2a;color: #bebebe;float:left}
            .trace-info{margin-left: 24px;margin-bottom:10px}
            .trace-line{font-family:monospace;color: #a29d9d;font-size:12px}
            .trace-right{width: 68%;float: right}
            .file-line{background-color: #322931;font: 13px monospace;line-height: 1.2em;padding: 3px 10px;text-align: left;color: #b9b5b8;margin: 0;border-bottom: 1px solid #464646;white-space: pre-wrap;word-wrap: break-word;}
            .trace-code{ margin: 0;margin-left: 30px }
            .trace-code-current{ background: rgba(255, 100, 100, .07) }
            .trace-hidden{display: none}
        </style>";
        $script = "<script>
            var box = document.querySelectorAll('.trace-box');
            var content = document.querySelectorAll('.trace-content');
    
            function show(event, id) {
                for (var i = 0; i < box.length; i++) {
                    box[i].classList.remove('active');
                    content[i].classList.add('trace-hidden');
                }
                event.classList.add('active');
                document.getElementById('dump-'+id).classList.remove('trace-hidden');
            }
        </script>";

        $html = '<div class="trace"><ul class="trace-left">';
        foreach ($this->debugBacktrace as $key => $value) {
            $html .= strtr(
                '<li onClick=":click" class="trace-box :active"><span class="trace-index">:index</span><div class="trace-info">:class:type:function:args</div><div class="trace-line">:file#:line</div></li>', [
                ':click' => "show(this,{$key})",
                ':active' => $key == 0 ? 'active' : '',
                ':index' => ++$key,
                ':class' => $value['class'] ?? '',
                ':type' => $value['type'] ?? '',
                ':function' => $value['function'] ?? '',
                ':args' => $value['args'] ? "(".implode(',', $value['args']).")" : '',
                ':file' => $value['file'],
                ':line' => $value['line'],
            ]);
        }
        $html .= '</ul><div class="trace-right">';
        foreach ($this->debugBacktrace as $key => $value) {
            $code = strtr('<ol class="trace-code" start=":start">', [":start" => (int)$value['start'] + 1]);
            foreach ($value['lines'] as $index => $line) {
                $code .= strtr('<li class=":current">:line</li>', [
                    ":line" => htmlentities($line),
                    ":current" => $value['line'] - $value['start'] - 1 == $index ? 'trace-code-current' : ''
                ]);
            }
            $code .= '<ol>';

            $html .= strtr('<div class="trace-content :hidden" id=":key"><div class="file-line">:code</div></div>', [
                ':hidden' => $key != 0 ? 'trace-hidden' : '',
                ':key' => "dump-{$key}",
                ':code' => $code,
            ]);
        }
        $html .= '</div></div>';
        return $html . $style . $script;
    }
}
