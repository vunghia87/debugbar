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
 * Collects string|json of Response
 */
class ResponseCollector extends DataCollector implements Renderable
{
    /**
     * @var mixed|string
     */
    protected $content;

    public function __construct($content = '')
    {
        $this->content = $content;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'response';
    }

    /**
     * @return array
     */
    public function collect()
    {
        if ($this->isJson($this->content)) {
            return json_decode($this->content, true);
        }

        return ['Html' => $this->parseTitle($this->content)];
    }

    protected function parseTitle($contents)
    {
        return preg_match('/<title[^>]*>(.*?)<\/title>/ims', $contents, $matches) ? $matches[1] : 'Invalid Title';
    }

    protected function isJson($string)
    {
        if (!is_string($string)) return false;
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * {@inheritDoc}
     */
    public function getWidgets()
    {
        return array(
            "response" => array(
                "icon" => "eye",
                "widget" => "PhpDebugBar.Widgets.VariableListWidget",
                "map" => "response",
                "default" => ""
            )
        );
    }
}
