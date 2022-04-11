<?php

namespace DebugBar\DataCollector;

class XdebugTraceCollector extends DataCollector
{
    public function collect()
    {
        return [
            'trace_file' => xdebug_get_tracefile_name()
        ];
    }

    function getName()
    {
        return 'xdebug_trace';
    }
}
