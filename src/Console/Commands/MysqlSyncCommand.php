<?php

namespace N3XT0R\MySqlSync\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use N3XT0R\MysqlSync\SyncService;
use Symfony\Component\Console\Input\InputOption;

class MysqlSyncCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'db:sync {environment}';


    public function handle(): int
    {
        $exitCode = 0;
        if ($this->confirmToProceed()) {
            $environment = $this->argument('environment');
            /**
             * @var SyncService $syncService
             */
            $syncService = $this->getLaravel()->get(SyncService::class);
            if (false === $syncService->sync($environment)) {
                $exitCode = 1;
            }
        }
        return $exitCode;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['environment', null, InputOption::VALUE_OPTIONAL, 'The environment to sync e.g. production'],
        ];
    }
}