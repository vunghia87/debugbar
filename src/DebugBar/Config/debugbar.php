<?php

return [
    // You can override the value by setting enable to true or false instead of null.
    'enabled' => getenv('DEBUGBAR_ENABLED') ?? null,

    // Only save collect data, not show debugBar on browser
    'watch' => true,

    // You can provide an array of URI's that must be ignored (eg. 'api/*')
    'except' => [
        'api*',
    ],

    /*
      |--------------------------------------------------------------------------
      | Editor
      |--------------------------------------------------------------------------
      |
      | Choose your preferred editor to use when clicking file name.
      |
      | Supported: "phpstorm", "vscode", "vscode-insiders", "vscode-remote",
      |            "vscode-insiders-remote", "vscodium", "textmate", "emacs",
      |            "sublime", "atom", "nova", "macvim", "idea", "netbeans",
      |            "xdebug", "espresso"
      |
      */
    'editor' => getenv('DEBUGBAR_EDITOR') ?? 'phpstorm',

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
     | Remote Path Mapping
     |--------------------------------------------------------------------------
     |
     | If you are using a remote dev server, like Laravel Homestead, Docker, or
     | even a remote VPS, it will be necessary to specify your path mapping.
     |
     | Leaving one, or both of these, empty or null will not trigger the remote
     | URL changes and Debugbar will treat your editor links as local files.
     |
     | "remote_sites_path" is an absolute base path for your sites or projects
     | in Homestead, Vagrant, Docker, or another remote development server.
     |
     | Example value: "/home/vagrant/Code"
     |
     | "local_sites_path" is an absolute base path for your sites or projects
     | on your local computer where your IDE or code editor is running on.
     |
     | Example values: "/Users/<name>/Code", "C:\Users\<name>\Documents\Code"
     |
     */
    'remote_sites_path' => getenv('DEBUGBAR_REMOTE_SITES_PATH'),
    'local_sites_path' => getenv('DEBUGBAR_LOCAL_SITES_PATH'),

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
        'request'         => true,  // Regular or special request logger
        'db'              => false, // Show database (PDO) queries and bindings
        'response'        => false, // Show response by ob_start
        'memcache'        => false, // Show cache by memcache
        'auth'            => false, // get Global auth from session by Partner
        'command'         => true,  // get command execution from binary php
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
        'request' => [
            'shouldServer' => false
        ],
        'db' => [
            'skip' => [],
            'listen' => []
        ],
    ],
];