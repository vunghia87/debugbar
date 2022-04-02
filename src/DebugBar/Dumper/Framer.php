<?php

namespace DebugBar\Dumper;

class Framer
{
    protected $debugBacktrace;
    protected $backtraceExcludePaths = [];
    protected $fileContentsCache = [];
    protected $length = 150;
    protected $maxTrace = 20;
    protected static $isHeaderDumped = false;
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
            if ($this->fileIsInExcludedPath($trace['file']) || !isset($trace['file'])) {
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
        $debugBacktrace = $this->debugBacktrace;

        $html = '<div class="stack"><div class="stack-nav"><ul class="stack-frames-scroll">';
        foreach ($debugBacktrace as $key => $value) {
            $class = $value['class'] ?? '';
            $class = is_object($class) ? get_class($class) : $class;

            $args = array_map(function ($arg) {
                return $this->objToString($arg);
            }, $value['args'] ?? []);

            $fileArr = explode('/', trim($value['file'],'/'));
            $files = array_map(function ($f) {
                return "<span>/{$f}<wbr></span>";
            }, $fileArr);

            $end = end($fileArr);
            $endArr = explode('.', $end);
            $mime = end($endArr);

            $html .= strtr(
                '<li class="stack-frame :active" key=":key"><span class="stack-frame-number">:index</span><div class="stack-frame-text"><span class="stack-frame-header">:file</span><span class="stack-frame-class">:class:type:function:args</span></div><div class="stack-frame-line"> ::line</div></li>', [
                ':key' => $key,
                ':active' => $key == 0 ? 'active' : '',
                ':index' => ++$key,
                ':class' => $class,
                ':type' => $value['type'] ?? '',
                ':function' => $value['function'] ?? '',
                ':args' => is_string($value['args']) ? $value['args'] : "(" . implode(',', $args) . ")",
                ':file' => join($files),
                ':line' => $value['line'],
            ]);
        }
        $html .= '</ul></div><div class="stack-main"><div class="stack-main-content">';

        foreach ($debugBacktrace as $key => $value) {
            $lines = '<div class="stack-lines">';
            $code = strtr('<pre class="stack-code" key=":key"><code lang=":lang">', [":key" => $key, ":lang" => $mime]);
            foreach ($value['lines'] as $index => $line) {
                $current = $value['start'] + $index + 1;
                $lines .= strtr('<p class="stack-line"> <a target="_blank" href=":editorHref">:index </a></p>',[
                    ":index" => $current,
                    ":editorHref" => $this->getEditorHref($value['file'], (int)$current)
                ]);
                $code .= strtr('<p class="stack-code-line :current">:line</p>', [
                    ":line" => htmlentities($line),
                    ":current" => $value['line'] - $value['start'] - 1 == $index ? 'stack-code-current' : '',
                ]);
            }
            $lines .= "</div>";
            $code .= '</code></pre>';

            $html .= strtr('<div class="stack-viewer :hidden" key=":key"><div class="stack-ruler">:lines</div>:code</div>', [
                ':hidden' => $key != 0 ? 'stack-hidden' : '',
                ':key' => "dump-{$key}",
                ':code' => $code,
                ':lines' => $lines,
            ]);
        }
        $html .= '</div></div></div>';
        return $html . $this->getHeader();
    }

    protected function getEditorHref(string $file, int $line)
    {
        if (REMOTE_PATH == null || LOCAL_PATH == null) {
            return null;
        }
        $filePath = trim(str_replace(trim(REMOTE_PATH, '/'), '', $file), '/');
        $folderPath = trim(LOCAL_PATH, '\\') . '\\' . str_replace('/', '\\', $filePath);
        return str_replace(['%file', '%line'], [$folderPath, $line], $this->editors[$this->editor]);
    }

    protected function objToString($obj)
    {
        if (is_null($obj)) {
            return 'null';
        }
        if (is_string($obj)) {
            return "'" . $obj . "'";
        }
        if (is_numeric($obj)) {
            return $obj;
        }
        if (is_array($obj)) {
            return "[" . implode(',', $obj) . "]";
        }
        if (is_object($obj)) {
            return "Object(" . get_class($obj) . ")";
        }
        if (is_callable($obj)) {
            return "Closure()";
        }
        if (is_resource($obj)) {
            return "Resource#";
        }
        return '';
    }

    public function getHeader()
    {
        if (static::$isHeaderDumped) {
            return '';
        }

        static::$isHeaderDumped = true;

        $style = "<style>
            *{margin: 0;padding:0}
            .stack {font-family: monospace;display: grid;align-items: stretch;grid-template: calc(100vh - 1rem) / 22rem 1fr}
            .stack-nav{padding: 0;background: #262632;border-bottom-width: 0;border-right: 1px solid;overflow: hidden;height: 100%;border-color: #4b4b55;display: grid; grid-template: auto 1fr / 100%;position: relative;}
            .stack-frames-scroll{position: absolute;top: 0;right: 0;bottom: 0;left: 0;overflow-x: hidden;overflow-y: auto}
            .stack-frame{cursor: pointer;transition: all 0.1s ease;display: grid; align-items: flex-end;grid-template-columns: 2rem 1fr auto;border-bottom: 1px solid; border-color: #4b4b55;background-color: #262632;}
            .stack-frame.active{background-color: #3c2e60;z-index: 10;}
            .stack-frame.active .stack-frame-text { border-color: #7900F5FF;}
            .stack-frame-number{padding:0.5rem;color: #7e6ba7;font-feature-settings: 'tnum';font-variant-numeric: tabular-nums;text-align: center}
            .stack-frame-text{display: grid;align-items: center;grid-gap: 0.5rem; border-left: 2px solid;padding-left: 0.75rem;padding-top: 0.5rem;padding-bottom: 0.5rem;color: #d8d8df;border-color: #4b4b55}
            .stack-frame-header{margin-right: -20rem;width: 100%;justify-content: flex-start;align-items: baseline;display: inline-block;line-height: 1.25;word-break: break-word;}
            .stack-frame-class{display: inline-block;line-height: 1.25;color: #f0f0f58c;word-break: break-all;}
            .stack-frame-line{margin: 0.5rem 0.5rem 0.5rem 0.25rem;padding:0.2rem;text-align: right;line-height: 1.25;display: inline-block;background-color: #f0f0f50d;color: #f0f0f58c;font-size: 0.75rem}
            .stack-main{display: grid;height: 100%;overflow: hidden;background-color: #30303a;grid-template: auto 1fr / 100%;position: relative}
            .stack-main-content{overflow: hidden;}
            .stack-viewer{position: absolute; top: 0;right: 0;bottom: 0;left: 0;overflow: auto;background-color: #262632;font-size: 0.8rem;display: flex}
            .stack-ruler{position: sticky;flex: none;left: 0;z-index: 20;}
            .stack-lines{min-height: 100%;border-right: 1px solid; border-color: #eeeef5;background-color: #30303a;padding-top: 1rem; padding-bottom: 1rem; -webkit-user-select: none;-moz-user-select: none;-ms-user-select: none;user-select: none;}
            .stack-line{padding-left: 0.5rem;padding-right: 0.5rem;color: #f0f0f58c;line-height: 2;-webkit-user-select: none;-moz-user-select: none;-ms-user-select: none;user-select: none;cursor: pointer;text-align: right}
            .stack-line a{color: #fff;text-decoration: none;}
            .stack-line:hover{background: #686861}
            .stack-code{ flex-grow: 1;padding-top: 1rem;padding-bottom: 1rem;}
            .stack-code-line{padding-left: 1rem;color: #d8d8df;line-height: 2;}
            .stack-code-line span{}
            .stack-code-line:hover, .stack-code-current{background: #3c2e60}
            .stack-hidden{display: none}
        </style>";

        $script = '<script>
            var frames = document.getElementsByClassName("stack-frame");
            for (var i = 0; i < frames.length; i++) {
                frames[i].addEventListener("click", function(event) {
                    var element = event.currentTarget;
                    var stack = element.closest(".stack");
                    var frame = stack.querySelectorAll(".stack-frame");
                    var content = stack.querySelectorAll(".stack-viewer");
                    for (var i = 0; i < frame.length; i++) {
                        frame[i].classList.remove("active");
                        content[i].classList.add("stack-hidden");
                    }
                    var id = element.getAttribute("key");
                    element.classList.add("active");
                    var current = stack.querySelector(`[key="dump-${id}"]`);
                    current.classList.remove("stack-hidden");
                    var lang = current.getAttribute("lang") ?? "php";
                    var codes = current.querySelectorAll("code > p");
                    var j = 0, length = codes.length;
                    for ( j; j < length; j++) {
                         var codeHtml = hljs.highlight(lang,codes[j].innerText).value;
                         codes[j].innerHTML = codeHtml;
                    }
                });
                if(i == 0) frames[i].click();
            }
        </script>';

        //read https://highlightjs.org/
        $vendor = '<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.5.0/styles/androidstudio.min.css">';
        $vendor .= '<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.5.0/highlight.min.js"></script>';
        //$vendor .= '<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.5.0/languages/php.min.js"></script>';
        return $style . $vendor . $script;
    }
}
