<?php

namespace Tests;

use Illuminate\Foundation\Application;
use N3XT0R\MySqlSync\Providers\MySqlSyncServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected $resourceFolder = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->resourceFolder = __DIR__ . '/Resources/';
    }

    /**
     * @param Application $app
     * @return array|string[]
     */
    protected function getPackageProviders($app): array
    {
        return [
            MySqlSyncServiceProvider::class,
        ];
    }

    /**
     * @param Application $app
     * @return array
     */
    protected function getPackageAliases($app): array
    {
        return [
        ];
    }

    /**
     * @param Application $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'mysql');
        $app['config']->set(
            'database.connections.mysql',
            [
                'host' => env('DB_HOST', 'db_sync'),
                'driver' => 'mysql',
                'database' => 'testing',
                'username' => 'root',
                'password' => '',
                'prefix' => '',
            ]
        );
    }
}
