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
     * Sets a flag indicating whether the Symfony HtmlDumper will be used to dump variables for
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
     * Indicates whether the Symfony HtmlDumper will be used to dump variables for rich variable
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

    /**
     * @return array
     */
    public function collect()
    {
        $vars = array('_GET', '_POST', '_SESSION', '_COOKIE', '_SERVER');
        if (!$this->shouldServer) {
            unset($vars[4]);
        }
        $data = array();

        foreach ($vars as $index => $var) {
            if (isset($GLOBALS[$var])) {
                $key = "$" . $var;
                $data[$index]['method'] = $key;
                if ($this->isHtmlVarDumperUsed()) {
                    $data[$index]['value'] = $this->getVarDumper()->renderVar($GLOBALS[$var]);
                } else {
                    $data[$index]['value'] = $this->getDataFormatter()->formatVar($GLOBALS[$var]);
                }
            }
        }

        return $data;
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
    public function getAssets() {
        $dumperAsset = $this->isHtmlVarDumperUsed() ? $this->getVarDumper()->getAssets() : array();
        return array_merge(['js' => 'widgets/requests/widget.js'], $dumperAsset);

        //return $this->isHtmlVarDumperUsed() ? $this->getVarDumper()->getAssets() : array();
    }

    /**
     * @return array
     */
    public function getWidgets()
    {
        $widget = $this->isHtmlVarDumperUsed()
            ? "PhpDebugBar.Widgets.HtmlVariableListWidget"
            : "PhpDebugBar.Widgets.VariableListWidget";
        return array(
            "request" => array(
                "icon" => "tags",
                "widget" => "PhpDebugBar.Widgets.RequestWidget",
                "map" => "request",
                "default" => "{}"
            )
        );
    }
}
