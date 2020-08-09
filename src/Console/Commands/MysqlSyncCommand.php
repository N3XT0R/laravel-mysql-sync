<?php

namespace N3XT0R\MySqlSync\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\App;
use N3XT0R\MySqlSync\Service\SyncService;
use Symfony\Component\Console\Input\InputOption;

class MysqlSyncCommand extends Command
{
    use ConfirmableTrait;

    protected $name = 'db:sync';


    public function handle(): int
    {
        $exitCode = 0;
        $output = $this->getOutput();
        $run = 'production' !== App::environment();
        if (false === $run) {
            $run = $this->confirmToProceed();
        }

        if (true === $run) {
            $environment = (string)$this->option('connection');
            /**
             * @var SyncService $syncService
             */
            $syncService = $this->getLaravel()->get(SyncService::class);
            $syncService->setOutput($output);
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
            ['connection', null, InputOption::VALUE_REQUIRED, 'The environment to sync e.g. production'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production'],
        ];
    }
}