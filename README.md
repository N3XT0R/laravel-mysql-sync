# laravel-mysql-sync

This Tool allows Laravel/Lumen Developers to syncing mysql databases to the local environment from any configured
environment like staging or production systems.

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

