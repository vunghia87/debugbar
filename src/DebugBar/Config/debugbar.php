<?php

return [
    // You can override the value by setting enable to true or false instead of null.
    'enabled' => getenv('DEBUGBAR_ENABLED') ?? null,

    // You can provide an array of URI's that must be ignored (eg. 'api/*')
    'except' => [
        'api*',
    ],

    /*
      |--------------------------------------------------------------------------
      | Custom Error Handler for Deprecated warnings
      |--------------------------------------------------------------------------
      |
      | When enabled, the Debugbar shows deprecated warnings for Exception Tab.
      |
      */
    'error_handler' => false,

    // public Resources can access js/css/image
    'assets_sites_url' => getenv('DEBUGBAR_ASSET_SITES_URL') ?? '',


    /*
      |--------------------------------------------------------------------------
      | Storage settings
      |--------------------------------------------------------------------------
      |
      | DebugBar stores data for session/ajax requests.
      | You can disable this, so the debugbar stores data in headers/session,
      | but this can cause problems with large data collectors.
      | By default, file storage (in the storage folder) is used. Redis and PDO
      | can also be used. For PDO, run the package migrations first.
      |
      */
    'storage' => [
        'enabled'    => true,
        'driver'     => 'file', // redis, file, pdo, memcache, custom
        'path'       => sys_get_temp_dir(), // For file driver
        'connection' => null,   // Leave null for default connection (Redis/PDO)
        'provider'   => '', // Instance of StorageInterface for custom driver
//        'hostname'   => '127.0.0.1', // Hostname to use with the "socket" driver
//        'port'       => 2304, // Port to use with the "socket" driver
    ],

    /*
     |--------------------------------------------------------------------------
     | DataCollectors
     |--------------------------------------------------------------------------
     |
     | Enable/disable DataCollectors
     |
     */

    'collectors' => [
        'phpinfo'         => true,  // Php version
        'messages'        => true,  // Messages
        'time'            => true,  // Time Datalogger
        'memory'          => true,  // Memory usage
        'exceptions'      => true,  // Exception displayer
        'request'         => true,  // Regular or special Symfony request logger
        'db'              => false, // Show database (PDO) queries and bindings
        'response'        => false, // Show response by ob_start
        'memcache'        => false, // Show cache by memcache
        'auth'            => true,  // get Global auth from session by Partner
    ],

    /*
    |--------------------------------------------------------------------------
    | Extra options
    |--------------------------------------------------------------------------
    |
    | Configure some DataCollectors
    |
    */

    'options' => [
        'memcache' => [
            'shouldValue' => false
        ],
        'messages' => [
            'shouldTrace' => false
        ],
    ],
];