<?php

namespace N3XT0R\MySqlSync\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\ConnectionResolverInterface as Resolver;
use N3XT0R\MysqlSync\SyncService;
use Symfony\Component\Console\Input\InputOption;

class MysqlSyncCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'db:sync {environment}';

    /**
     * The connection resolver instance.
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected $resolver;

    /**
     * Create a new database seed command instance.
     *
     * @param \Illuminate\Database\ConnectionResolverInterface $resolver
     * @return void
     */
    public function __construct(Resolver $resolver)
    {
        parent::__construct();
        $this->setResolver($resolver);
    }

    public function setResolver(Resolver $resolver): void
    {
        $this->resolver = $resolver;
    }

    public function getResolver(): Resolver
    {
        return $this->resolver;
    }

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
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production'],
        ];
    }
}