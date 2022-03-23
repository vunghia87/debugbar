<?php

namespace DebugBar\Loader;

use Helper;
use Loan;
use Throwable;

class ProxyNewRelic
{
    const TRACE_PARAM_LOAN_PUBLIC_ID = 'loanPublicID';
    /**
     * Check to see if extension NewRelic is installed and loaded.
     * @return bool
     */
    public static function isNewRelicLoaded()
    {
        return extension_loaded('newrelic');
    }

    /** https://docs.newrelic.com/docs/agents/php-agent/php-agent-api/newrelic_notice_error
     * When there are multiple calls to this function in a single transaction, the PHP agent retains the exception from the last call only.
     */
    public static function noticeError($data){
        if (extension_loaded('newrelic'))
        {
            newrelic_notice_error($data);
        }
        static::addThrowable($data);
    }

    /**
     * Notice an exception to NewRelic
     * @link https://docs.newrelic.com/docs/agents/php-agent/php-agent-api/newrelic_notice_error
     * @param $exception
     */
    public static function noticeException($exception)
    {
        if (self::isNewRelicLoaded()) {
            newrelic_notice_error(null, $exception);
        }
        static::addThrowable($exception);
    }

    /** Add a parameter to a trace to show in New Relic
     * @param $key
     * @param $value
     */
    public static function addCustomParameter($key,$value){
        if (extension_loaded('newrelic')) {
            newrelic_add_custom_parameter($key, $value);
        }
    }

    /**
     * @param $array
     */
    public static function addCustomParameterArray( $array ){
        if (empty($array)){
            return;
        }
        if (extension_loaded('newrelic')) {
            foreach ($array as $key => $value){
                if (is_array($value) || is_object($value)){
                    $printValue = json_encode($value);
                } else {
                    $printValue = $value;
                }
                newrelic_add_custom_parameter($key, $printValue);
            }
        } elseif (Helper::isLocalhost()){
            //This is called on nearly every trace now so it's super spammy.
            //Helper::errorLogPrintR($array);
        }
    }
    /**
     * @param Loan $loan
     */
    public static function addLoanParametersToTrace( $loan ){
        $parameterArray = [
            "loanID" => $loan->id,
            "loanPublicID" => $loan->publicID,
            "lenderID" => $loan->lenderID,
        ];
        NewRelic::addCustomParameterArray( $parameterArray );
    }

    /**
    https://docs.newrelic.com/docs/agents/php-agent/php-agent-api/newrelic_record_custom_event
    The agent records a maximum of 10,000 events per minute. Limit the number of unique event type names that you create, and do not generate these names dynamically.
    Avoid using Insights reserved words and characters for the event and attributes names.
    Ensure you do not exceed the event size and rate restrictions.
     * @param $name
     * @param $attributes
     */
    public static function recordEvent( $name, $attributes ) {
        if ( extension_loaded( 'newrelic' ) ) {
            newrelic_record_custom_event( $name, $attributes );
        } elseif (Helper::isLocalhost()){
            error_log($name);
            Helper::errorLogPrintR($attributes);
        }
    }
    /** https://docs.newrelic.com/docs/agents/php-agent/php-agent-api/newreliccustommetric-php-agent-api
     * @param string $metricName
     * @param float $time timing in seconds or milliseconds
     * @param bool $inSeconds Due to the simplest timing mechanism in PHP returning in seconds, we'll take it in seconds by default. False assumes milliseconds
     */
    public static function recordMetric( $metricName, $time, $inSeconds = true ) {
        if ( extension_loaded( 'newrelic' ) ) {
            if ($inSeconds){
                $time *= 1000;
            }
            newrelic_custom_metric( 'Custom/' . $metricName, $time );
        }
    }

    protected static function addThrowable($exception)
    {
        error_log($exception);
        if ($exception instanceof Throwable) {
            return debugbar()->addThrowable($exception);
        }

        return debugbar()->error($exception);
    }
}