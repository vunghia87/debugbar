<?php

require __DIR__ . '/Dumper/dump.php';

if (!function_exists('event')) {
    function event()
    {
        return DebugBar\Events\Dispatcher::getInstance();
    }
}

if (!function_exists('debugbar')) {
    function debugbar()
    {
        return \DebugBar\AdvanceDebugBar::getInstance();
    }
}

if (!function_exists('info')) {
    function info(...$var)
    {
        debugbar()->info($var);
    }
}

if (!function_exists('report')) {
    function report($exception)
    {
        if ($exception instanceof Throwable) {
            return debugbar()->addThrowable($exception);
        }

        return debugbar()->error($exception);
    }
}

if (!function_exists('time_human')) {
    /**
     * @param string $datetime 2013-05-01 00:22:35 | @1367367755
     * @param bool $full
     * @return string
     * @throws Exception
     */
    function time_human(string $datetime, bool $full = false)
    {
        $now = new \DateTime;
        $ago = new \DateTime($datetime);
        $diff = $now->diff($ago);

        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;

        $string = array(
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        );
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }

        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' ago' : 'just now';
    }
}
