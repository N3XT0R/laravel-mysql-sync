<?php

namespace N3XT0R\MySqlSync\Providers;

use Collective\Remote\RemoteFacade;
use Collective\Remote\RemoteServiceProvider;
use Illuminate\Support\ServiceProvider;
use N3XT0R\MysqlSync\Console\Commands;

class MySqlSyncServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->bootCommand();
        $this->publishes(
            [
                __DIR__ . '/../Config/sync-config.php' => $this->config_path('sync-config.php'),
            ],
            'mysql-sync'
        );
    }

    /**
     * function to make able use this library on lumen, too.
     * @param string $path
     * @return string
     */
    private function config_path(string $path = ''): string
    {
        return app()->basePath() . 'config' . DIRECTORY_SEPARATOR . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/sync-config.php', 'mysql-sync');
        $this->registerCollective();
    }

    protected function registerCollective(): void
    {
        $this->app->register(RemoteServiceProvider::class);
        $this->app->alias('SSH', RemoteFacade::class);
        /**
         * @var \Illuminate\Config\Repository $config
         */
        $config = $this->app->get('config');
        $connections = $config->get('remote.connections');
        $syncConnections = $config->get('mysql-sync.connections');
        $mergedConnections = array_replace_recursive($connections, $syncConnections);
        $config->set('remote.connections', $mergedConnections);
    }

    protected function bootCommand(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands(
                [
                    Commands\MysqlSyncCommand::class,
                ]
            );
        }
    }
}