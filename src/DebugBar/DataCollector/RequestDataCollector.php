<?php
/*
 * This file is part of the DebugBar package.
 *
 * (c) 2013 Maxime Bouroumeau-Fuseau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DebugBar\DataCollector;

/**
 * Collects info about the current request
 */
class RequestDataCollector extends DataCollector implements Renderable, AssetProvider
{
    // The HTML var dumper requires debug bar users to support the new inline assets, which not all
    // may support yet - so return false by default for now.
    protected $useHtmlVarDumper = true;

    /**
     * Sets a flag indicating whether the HtmlDumper will be used to dump variables for
     * rich variable rendering.
     *
     * @param bool $value
     * @return $this
     */
    public function useHtmlVarDumper($value = true)
    {
        $this->useHtmlVarDumper = $value;
        return $this;
    }

    /**
     * Indicates whether the HtmlDumper will be used to dump variables for rich variable
     * rendering.
     *
     * @return mixed
     */
    public function isHtmlVarDumperUsed()
    {
        return $this->useHtmlVarDumper;
    }

    protected $shouldServer = false;

    public function __construct($shouldServer = false)
    {
        $this->shouldServer = $shouldServer;
    }

    public static $statusTexts = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',            // RFC2518
        103 => 'Early Hints',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',          // RFC4918
        208 => 'Already Reported',      // RFC5842
        226 => 'IM Used',               // RFC3229
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',    // RFC7238
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Content Too Large',                                           // RFC-ietf-httpbis-semantics
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',                                               // RFC2324
        421 => 'Misdirected Request',                                         // RFC7540
        422 => 'Unprocessable Content',                                       // RFC-ietf-httpbis-semantics
        423 => 'Locked',                                                      // RFC4918
        424 => 'Failed Dependency',                                           // RFC4918
        425 => 'Too Early',                                                   // RFC-ietf-httpbis-replay-04
        426 => 'Upgrade Required',                                            // RFC2817
        428 => 'Precondition Required',                                       // RFC6585
        429 => 'Too Many Requests',                                           // RFC6585
        431 => 'Request Header Fields Too Large',                             // RFC6585
        451 => 'Unavailable For Legal Reasons',                               // RFC7725
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',                                     // RFC2295
        507 => 'Insufficient Storage',                                        // RFC4918
        508 => 'Loop Detected',                                               // RFC5842
        510 => 'Not Extended',                                                // RFC2774
        511 => 'Network Authentication Required',                             // RFC6585
    ];

    /**
     * @return \stdClass
     */
    public function collect()
    {
        return $this->http();
    }

    /**
     * @return \stdClass
     */
    protected function http()
    {
        $vars = array('_GET', '_POST', '_SESSION', '_COOKIE', '_SERVER');
        if (!$this->shouldServer) {
            unset($vars[4]);
        }
        $data = array();

        if (isset($GLOBALS['_SERVER'])) {
            $key = "$" . '_URL';
            $data[0]['method'] = $key;

            $statusCode = http_response_code() ?? 200;

            $info = [
                'url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?'),
                'status_code' => $statusCode,
                'status_text' => static::$statusTexts[$statusCode] ?? 'unknown status',
                'script_filename' => isset($_SERVER['SCRIPT_FILENAME']) ? $this->normalizeFilename($_SERVER['SCRIPT_FILENAME']) : ''
            ];

            $data[0]['value'] = $info;
        }

        foreach ($vars as $index => $var) {
            $index = $index + 1;
            if (isset($GLOBALS[$var])) {
                $key = "$" . $var;
                $data[$index]['method'] = $key;
                $data[$index]['value'] = $GLOBALS[$var];
            }
        }

        foreach ($data as $index => $item) {
            $data[$index]['value'] = $this->isHtmlVarDumperUsed()
                ? $this->getVarDumper()->renderVar($item['value'])
                : $this->getDataFormatter()->formatVar($item['value']);
        }

        $request = new \stdClass();
        $request->data = $data;
        $request->status_code = $statusCode;
        return $request;
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

        return str_replace($this->getHtmlRootFolder(), '', $path);
    }

    /**
     * @param string $root
     * @return array|string|string[]|null
     */
    protected function getHtmlRootFolder(string $root = '/var/www/')
    {
        $ret = str_replace(' ', '', $_SERVER['DOCUMENT_ROOT'] ?? '');
        $ret = rtrim($ret, '/') . '/';

        if (!preg_match("#" . $root . "#", $ret)) {
            $root = rtrim($root, '/') . '/';
            $root_arr = explode("/", $root);
            $pwd_arr = explode("/", getcwd());
            $ret = $root . $pwd_arr[count($root_arr) - 1];
        }

        $dir = (preg_match("#" . $root . "#", $ret)) ? rtrim($ret, '/') . '/' : null;
        return str_replace('public_html/', '', $dir);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'request';
    }

    /**
     * @return array
     */
    public function getAssets()
    {
        return [
            'js' => 'widgets/requests/widget.js'
        ];
        //move debugbar css + js
        //$dumperAsset = $this->isHtmlVarDumperUsed() ? $this->getVarDumper()->getAssets() : array();
        //return array_merge(['js' => 'widgets/requests/widget.js'], $dumperAsset);
    }

    /**
     * @return array
     */
    public function getWidgets()
    {
        return array(
            "request" => array(
                "icon" => "tags",
                "widget" => "PhpDebugBar.Widgets.RequestWidget",
                "map" => "request.data",
                "default" => "{}",
            ),
            "status" => array(
                "icon" => "shield",
                "tooltip" => "Status Code",
                "map" => "request.status_code",
                "default" => ""
            )
        );
    }
}
