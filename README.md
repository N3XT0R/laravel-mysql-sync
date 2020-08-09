# laravel-mysql-sync [Laravel/Lumen 5.x /6]

This Tool allows Laravel/Lumen Developers to syncing mysql databases to the local environment from any configured
environment like staging or production systems.

## Requirements

### Remote
    - mysqldump
    - openssh
### Local
    - mysql-client

## Installation

```shell script
composer require --dev n3xt0r/laravel-mysql-sync
```

Add your new provider to the providers array of config/app.php
```php
    'providers' => [
        // ...
        \N3XT0R\MySqlSync\Providers\MySqlSyncServiceProvider::class,
        // ...
      ],
```

publish the config

```shell script
php artisan vendor:publish --provider="N3XT0R\MySqlSync\Providers\MySqlSyncServiceProvider"
```

### Lumen

Add the config from the vendor directory to your config dir manually. Dont forget to register it 
inside of the bootstrap/app.php.

## Configuration Example

```php
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
            'host' => 'example.com',
            'username' => 'myUser',
            'password' => '',
            'key' => storage_path('id_rsa'),
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
        'laravel' => [
            'connection' => 'production',
            'host' => 'mysql.example.com',
            'database' => 'myApp',
            'user' => 'root',
            'password' => 'myPassword',
        ],
        'secondOptionalDb' => [
            'connection' => 'production',
            'host' => 'mysql.example.com',
            'database' => 'customerDb',
            'user' => 'root',
            'password' => 'myPassword',
        ],
    ],
    'environments' => [
        'production' => [
            /**
            * be careful, this is the same order like on importing databases
            * when you have constraints between database, set them to correct order.
            */
            'databases' => [
                'laravel',
                'secondOptionalDb',
            ],
        ],
    ],
    /**
    * originally it should be the storage dir
    * but you could configure any other directory, too.
    */
    'storage' => storage_path(), 
];
```

## Execute syncing

```shell script
php artisan db:sync --stage=production
```
