<?php

/**
 * @mixin ProxyNewRelic
 */
class NewRelic extends ProxyNewRelic
{
    public static function noticeError($data)
    {
        static::addThrowable($data);
        parent::noticeError($data);
    }

    public static function noticeException($exception)
    {
        static::addThrowable($exception);
        parent::noticeError($exception);
    }

    private static function addThrowable($exception)
    {
        error_log($exception);
        if ($exception instanceof Throwable) {
            return debugbar()->addThrowable($exception);
        }

        return debugbar()->error($exception);
    }
}