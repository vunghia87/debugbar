<?php
/*
 * This file is part of the DebugBar package.
 *
 * (c) 2013 Maxime Bouroumeau-Fuseau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DebugBar\DataFormatter;

use DebugBar\Dumper\Dumper;

class DataFormatter implements DataFormatterInterface
{
    /** @var Dumper  */
    protected $dumper;

    public function __construct()
    {
        $this->dumper = (new Dumper())
            ->depth(3)
            ->filter(true)
            ->onlyVar(true)
            ->type('string');
    }

    /**
     * @param $data
     * @return string
     */
    public function formatVar($data)
    {
        return $this->dumper->dump($data);
    }

    /**
     * @param float $seconds
     * @return string
     */
    public function formatDuration($seconds)
    {
        if ($seconds < 0.001) {
            return round($seconds * 1000000) . 'μs';
        } elseif ($seconds < 0.1) {
            return round($seconds * 1000, 2) . 'ms';
        } elseif ($seconds < 1) {
            return round($seconds * 1000) . 'ms';
        }
        return round($seconds, 2) . 's';
    }

    /**
     * @param string $size
     * @param int $precision
     * @return string
     */
    public function formatBytes($size, $precision = 2)
    {
        if ($size === 0 || $size === null) {
            return "0B";
        }

        $sign = $size < 0 ? '-' : '';
        $size = abs($size);

        $base = log($size) / log(1024);
        $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
        return $sign . round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
    }
}
