<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Remote Server Connections
    |--------------------------------------------------------------------------
    |
    | These are the servers that will be accessible via the SSH task runner
    | facilities of Laravel. This feature radically simplifies executing
    | tasks on your servers, such as deploying out these applications.
    |
    */
    'connections' => [
        'production' => [
            'host' => '',
            'username' => '',
            'password' => '',
            'key' => '',
            'keytext' => '',
            'keyphrase' => '',
            'agent' => '',
            'timeout' => 10,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Remote Server Databases
    |--------------------------------------------------------------------------
    |
    | These are the databases that will be accessible for syncing.
    |
    */
    'databases' => [
        'db1' => [
            'connection' => 'production',
            'host' => 'localhost',
            'database' => 'homestead',
            'user' => 'root',
            'password' => '',
        ],
        'db2' => [
            'connection' => 'production',
            'host' => 'localhost',
            'database' => 'homestead',
            'user' => 'root',
            'password' => '',
        ],
    ],
    'environments' => [
        'production' => [
            'databases' => [
                'db1',
                'db2',
            ],
        ],
    ],
    'storage' => storage_path(),
];