<?php

namespace DebugBar\Dumper;

class Dumper
{
    protected $depth = 3;
    protected $skips = [];
    protected $methods = [];
    protected $styles = [
        'html' => [
            "pre" => "background-color:#262632;font: 13px monospace;line-height:1.2em;padding:3px 10px;text-align:left;color:#b9b5b8;margin:0;border-bottom:1px solid #464646;white-space:pre-wrap;word-wrap: break-word;",
        ],
    ];
    protected $filters = \ReflectionProperty::IS_PUBLIC;
    protected $onlyVar = true;
    protected $type = 'html'; // html, string, cli NOT_SUPPORT
    protected static $isHeaderDumped = false;

    public function depth(int $depth)
    {
        $this->depth = $depth;
        return $this;
    }

    public function skips(array $skips)
    {
        $this->skips = $skips;
        return $this;
    }

    public function filter(bool $isPublic)
    {
        $this->filters = $isPublic
            ? \ReflectionProperty::IS_PUBLIC
            : \ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PRIVATE | \ReflectionProperty::IS_STATIC;
        return $this;
    }

    public function onlyVar(bool $onlyVar)
    {
        $this->onlyVar = $onlyVar;
        return $this;
    }

    public function type(string $type)
    {
        $this->type = $type;
        return $this;
    }

    public function dump($variable, $name = null)
    {
        $this->methods = [];

        if ($this->type == 'html') {
            $template = [
                ":style" => $this->styles['html']['pre'],
                ":output" => $this->output($variable, $name)
            ];
            return $this->getDumpHeader() . strtr("<pre style=':style'>:output</pre>", $template);
        }

        if ($this->type == 'string') {
            return $this->outputString($variable, $name);
        }
    }

    protected function output($variable, $name = null, $tab = 1)
    {
        $space = "  ";
        $output = $name ? $name . " " : "";

        if (gettype($variable) == "array") {
            $count = \count($variable);
            if ($count == 0) {
                $output .= "<b class ='arr'>array</b> []";
                return $output;
            }

            $output .= strtr("<b class ='arr'>array</b> [<span class='dump arr' onclick='toggle(this)'>#:count </span><span class='dump-hidden'>\n", [":count" => $count]);

            foreach ($variable as $key => $value) {
                $output .= str_repeat($space, $tab) . strtr("[<span class='arr'>:key</span>] => ", [":key" => $key]);

                $output .= $this->output($value, "", $tab + 1) . "\n";
            }

            return $output . str_repeat($space, $tab - 1) . "</span>]";
        }

        if ($variable instanceof \Closure) {

            $r = new \ReflectionFunction($variable);

            $params = array();
            foreach ($r->getParameters() as $p) {
                $s = '';
                if ($p->isArray()) {
                    $s .= 'array ';
                } else if ($p->getClass()) {
                    $s .= $p->getClass()->name . ' ';
                }
                if ($p->isPassedByReference()) {
                    $s .= '&';
                }
                $s .= '$' . $p->name;

                if ($p->isDefaultValueAvailable()) {
                    $s .= ' = ' . var_export($p->getDefaultValue(), TRUE);
                }
                $params [] = $s;
            }
            $param = implode(', ', $params);

            $output .= strtr("<b class='obj'>closure</b>(<span class='other'>:var</span>) {<span class='dump' onclick='toggle(this)'>#4 </span><span> \n", [":var" => $param]);

            $methods = [
                'returnsReference' => "returnsReference",
                'returnType' => "getReturnType",
                'class' => "getClosureScopeClass",
                //'this' => "getClosureThis"
            ];

            foreach ($methods as $k => $m) {
                if (method_exists($r, $m) && false !== ($m = $r->$m()) && null !== $m) {
                    $value = $m instanceof \Reflector ? $m->name : $m;

                    $output .= str_repeat($space, $tab) . $this->output($value, $k, $tab + 1) . "\n";
                }
            }

            if ($v = $r->getStaticVariables()) {
                $output .= str_repeat($space, $tab) . $this->output($v, 'use', $tab + 1) . "\n";
            }

            $output .= str_repeat($space, $tab) . "file <span class='other'>" . $r->getFileName() . "</span>\n";

            $output .= str_repeat($space, $tab) . "line <span class='order'>" . $r->getStartLine() . ' to ' . $r->getEndLine() . "</span>\n";

            return $output . str_repeat($space, $tab - 1) . "</span>}";
        }

        if (gettype($variable) == "object") {

            $output .= strtr("class <b class='obj'>:class</b>", [":class" => get_class($variable)]);

            if (get_parent_class($variable)) {
                $output .= strtr(" extends <b class='obj'>:parent</b>", [":parent" => get_parent_class($variable)]);
            }

            if (!empty(class_implements($variable))) {
                $output .= strtr(" implements <b class='obj'>:implements</b>", [":implements" => implode(',', class_implements($variable))]);
            }

            $output .= " {";

            foreach ($this->skips as $skip) {
                if ($variable instanceof $skip) {
                    return $output . " ... }";
                }
            }

//            if (in_array(get_class($variable), $this->methods)) {
//                return $output . " listed... }";
//            }

            $debugClass = get_debug_type($variable);
            if ($debugClass === 'stdClass') {
                $attr = get_class_methods($variable);

                $output .= strtr("<span class='dump obj' onclick='toggle(this)'>#:count </span><span>", [":count" => count(get_object_vars($variable))]);

                $output .= "\n";

                foreach (get_object_vars($variable) as $key => $value) {
                    $output .= str_repeat($space, $tab) . strtr("-><span class=':type'>:type</span> <span class='variable'>:key</span> = ", [":key" => $key, ":type" => "public"]);
                    $output .= $this->output($value, "", $tab + 1) . "\n";
                }

                foreach ($attr as $value) {
                    $this->methods[] = get_class($variable);
                    $output .= str_repeat($space, $tab + 1) . strtr("-><span class='method'>:method</span>();\n", [":method" => $value]);
                }
            }
            elseif (in_array($debugClass, ['SimpleXMLElement','DOMDocument'])) {
                $variable = (array) $variable;
                $count = \count($variable);

                if($count == 0){
                    return $output . "}";
                }

                $output .= strtr("<span class='dump obj' onclick='toggle(this)'>#:count </span><span>", [":count" => $count]);
                $output .= "\n";
                foreach ($variable as $key => $value) {
                    $output .= str_repeat($space, $tab) . strtr("<span class='variable'>:key</span> = ", [":key" => $key]);
                    $output .= $this->output($value, "", $tab + 1) . "\n";
                }
            }
            else {
                $this->methods[] = get_class($variable);

                $reflect = new \ReflectionClass($variable);

                if ($tab > $this->depth) {
                    return "{ $reflect->name... }";
                }

                $attr = $reflect->getMethods($this->filters);

                $props = $reflect->getProperties($this->filters);

                $output .= strtr("<span class='dump obj' onclick='toggle(this)'>#:count </span><span>", [":count" => count($attr) + count($props)]);
                $output .= "\n";

                if (!$this->onlyVar) {
                    foreach ($attr as $value) {
                        $this->methods[] = $value->class;

                        $type = implode(' ', \Reflection::getModifierNames($value->getModifiers()));
                        $cursor = '->';
                        if (strpos($type, 'static')) {
                            $type = 'static';
                            $cursor = '::';
                        }

                        $params = $reflect->getMethod($value->name)->getParameters();
                        $temp = implode(', ', $params);
                        $temp = preg_replace('!\s+!', ' ', strip_tags($temp));
                        $temp = preg_replace('/Parameter #[0-9] \[ | \]/', '$1', $temp);
                        $temp = preg_replace('/,/', '<span style="color:#fd8b19">,</span>', $temp);
                        $temp = "<span class='other'>" . $temp . '</span>';

                        $output .= str_repeat($space, $tab) . strtr($cursor . "<span class=':type'>:type</span> <b class='method'>:method</b>(:param) \n", [":method" => $value->name, ":param" => $temp, ":type" => $type]);
                    }
                }

                foreach ($props as $index => $property) {
                    $property->setAccessible(true);

                    $key = $property->getName();
                    $type = implode(' ', \Reflection::getModifierNames($property->getModifiers()));
                    $cursor = '->';
                    if (strpos($type, 'static')) {
                        $type = 'static';
                        $cursor = '::';
                    }

                    $output .= str_repeat($space, $tab) . strtr($cursor . "<span class=':type'>:type</span> <b class='variable'>$:key</b> = ", [":key" => $key, ":type" => $type]);

                    $output .= $this->output($property->getValue($variable), "", $tab + 1) . "\n";
                }
            }

            return $output . str_repeat($space, $tab - 1) . "</span>}";
        }

        if (is_string($variable)) {
            return $output . strtr("<b class='str'>string</b> (<span class='str'>:length</span>) \"<span class='str'>:var</span>\"", [":length" => strlen($variable), ":var" => nl2br(htmlentities($variable, ENT_IGNORE, "utf-8"))]);
        }

        if (is_int($variable)) {
            return $output . strtr("<b class='int'>int</b> (<span class='int'>:var</span>)", [":var" => $variable]);
        }

        if (is_float($variable)) {
            return $output . strtr("<b class='float'>float</b> (<span class='float'>:var</span>)", [":var" => $variable]);
        }

        if (is_numeric($variable)) {
            return $output . strtr("<b class='num'>numeric string</b> (<span class='num'>:length</span>) \"<span class='num'>:var</span>\"", [":length" => strlen($variable), ":var" => $variable]);
        }

        if (is_bool($variable)) {
            return $output . strtr("<b class='bool'>bool</b> (<span class='bool'>:var</span>)", [":var" => ($variable ? "TRUE" : "FALSE")]);
        }

        if (is_null($variable)) {
            return $output . strtr("<b class='null'>NULL</b>", []);
        }

        return $output . strtr("(<span class='other'>:var</span>)", [":var" => $variable]);
    }

    protected function outputString($variable, $name = null, $tab = 1)
    {
        return strip_tags($this->output($variable, $name, $tab));
    }

    public function getDumpHeader()
    {
        if (static::$isHeaderDumped) {
            return '';
        }

        static::$isHeaderDumped = true;

        $style = '<style>
            .dump{cursor: pointer;color:#fd8b19!important}
            .dump:hover{font-weight: bold}
            .dump:after{content: \'▼\'}
            .dump + span{}
            .dump.dump-show:after {content: \'▶\'}
            .dump.dump-show + span {display: none}
            .arr{color:#6897bb}
            .bool{color:#ff6868}
            .float{color:#ffb939}
            .int{color:#ffb939}
            .null{color:#fff}
            .num{color:#ffb939}
            .obj{color:#fdcc59}
            .method{color:#1290bf}
            .variable{color:#7ec699}
            .other{color:#fff}
            .res{color:#6a8759}
            .other{color:#fff}
            .str{color:#6a8759}
            .private{color:#d4b1c9}
            .protected{color:#f384d1}
            .public{color:#ff05b2}
            .static{color:#da3966}
            .pre{}
        </style>';

        $script = " <script>
            function toggle(e) {
                return e.classList.toggle('dump-show');
            }
            var dumps = document.getElementsByClassName('dump');
            var active = 0;
            function collapseAll(e) {
                if (active === 0) {
                    active = 1;
                    Array.prototype.forEach.call(dumps, function(el) {
                        el.classList.add('dump-show');
                    });
                } else {
                    active = 0;
                    Array.prototype.forEach.call(dumps, function(el) {
                        el.classList.remove('dump-show');
                    });
                }
            }
        </script>";

        return $style . $script . "<button style='position: fixed;right: 5px;top: 3px;z-index: 9999999;font-size: 11px;background: #000;color: #fff' onclick='collapseAll()'>Collapse</button></div>";
    }

    public function sanitizeOutput($buffer) {
        $search = array('/\>[^\S ]+/s', '/[^\S ]+\</s', '/(\s)+/s', '/<!--(.|\s)*?-->/');
        $replace = array('>', '<', '\\1', '');
        return preg_replace($search, $replace, $buffer);
    }
}