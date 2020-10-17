<?php

namespace N3XT0R\MySqlSync\Service;

use Collective\Remote\Connection;
use Collective\Remote\RemoteManager;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class SyncService
{

    protected $sshManager;
    protected $config;
    protected $storagePath = '';
    protected $output;


    public function __construct(RemoteManager $sshManager, Repository $config, string $storagePath)
    {
        $this->setSshManager($sshManager);
        $this->setConfig($config);
        $this->setStoragePath($storagePath);
    }

    /**
     * @return RemoteManager
     */
    public function getSshManager(): RemoteManager
    {
        return $this->sshManager;
    }

    /**
     * @param RemoteManager $sshManager
     */
    public function setSshManager(RemoteManager $sshManager): void
    {
        $this->sshManager = $sshManager;
    }

    /**
     * @return Repository
     */
    public function getConfig(): Repository
    {
        return $this->config;
    }

    /**
     * @param Repository $config
     */
    public function setConfig(Repository $config): void
    {
        $this->config = $config;
    }

    /**
     * @return string
     */
    public function getStoragePath(): string
    {
        return $this->storagePath;
    }

    /**
     * @param string $storagePath
     */
    public function setStoragePath(string $storagePath): void
    {
        $this->storagePath = $storagePath;
    }

    /**
     * @return OutputInterface
     */
    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    public function hasOutput(): bool
    {
        return null !== $this->output;
    }


    protected function getPreparedConnectionConfig(string $environment): array
    {
        $preparedConfig = [];
        $config = $this->getConfig();
        $databases = $config->get('mysql-sync.environments.' . $environment . '.databases', []);

        foreach ($databases as $database) {
            $dbConfig = $config->get('mysql-sync.databases.' . $database);
            if (!array_key_exists($dbConfig['connection'], $preparedConfig)) {
                $preparedConfig[$dbConfig['connection']] = [];
            }

            $preparedConfig[$dbConfig['connection']][] = [
                'host' => $dbConfig['host'],
                'database' => $dbConfig['database'],
                'user' => $dbConfig['user'],
                'password' => $dbConfig['password'],
                'tmp_path' => $config->get('mysql-sync.tmp_path', '/tmp') . DIRECTORY_SEPARATOR,
            ];
        }

        return $preparedConfig;
    }


    public function sync(string $environment, bool $useLocalDump = false): bool
    {
        $result = true;
        $connectionConfig = $this->getPreparedConnectionConfig($environment);


        foreach ($connectionConfig as $connection => $configs) {
            foreach ($configs as $config) {
                if (false === $this->runDatabaseCopy($connection, $config)) {
                    $result = false;
                }
            }
        }


        return $result;
    }


    protected function runDatabaseCopy(string $connectionName, array $config): bool
    {
        $result = false;
        $storagePath = $this->getStoragePath();
        $dbDefaultConfig = $this->getConfig()->get(
            'database.connections.' . $this->getConfig()->get('database.default')
        );
        /**
         * @var FilesystemManager $filesystem
         */
        $filesystem = Storage::getFacadeRoot();
        $adapter = $filesystem->createLocalDriver(['root' => $storagePath]);
        if (false === $adapter->has('dumps')) {
            $adapter->createDir('dumps');
            $adapter->put('dumps/.gitignore', '*');
        }
        $tmpName = $config['database'] . '_' . date('YmdHis') . '.sql.gz';
        $config['remotePath'] = $config['tmp_path'] . $tmpName;
        $config['relativeLocalPath'] = 'dumps' . DIRECTORY_SEPARATOR . $tmpName;
        $config['localPath'] = $storagePath . DIRECTORY_SEPARATOR . $config['relativeLocalPath'];


        $isDumped = $this->createMySqlDumpByConfig($connectionName, $config, $adapter);

        if (true === $isDumped) {
            $result = $this->importDatabase($dbDefaultConfig, $config);
        }

        return $result;
    }


    public function createMySqlDumpByConfig(
        string $connectionName,
        array $config,
        Filesystem $filesystem
    ): bool {
        $sshConn = $this->getSshManager()->connection($connectionName);
        if ($this->hasOutput()) {
            $this->getOutput()->writeln('dumping database ' . $config['database'] . ' started');
        }

        $this->runSSHCommand(
            $connectionName,
            $sshConn,
            [
                "mysqldump --routines --triggers -h{$config['host']} -u{$config['user']} -p'{$config['password']}' {$config['database']} " .
                "| sed -e 's/DEFINER[ ]*=[ ]*[^*]*\*/\*/' " .
                "| gzip -9 > " . $config['remotePath']
            ]
        );

        $sshConn->get($config['remotePath'], $config['localPath']);

        $this->runSSHCommand(
            $connectionName,
            $sshConn,
            [
                'rm -f ' . $config['remotePath']
            ],
            3
        );

        if ($this->hasOutput()) {
            $this->getOutput()->writeln('dumping database ' . $config['database'] . ' finished');
        }

        return $filesystem->exists($config['relativeLocalPath']);
    }

    public function importDatabase(array $dbDefaultConfig, array $config): bool
    {
        $hasOutput = $this->hasOutput();
        $result = false;
        if (true === DB::connection()->unprepared(
                'DROP DATABASE IF EXISTS  `' . $config['database'] . '`; CREATE DATABASE `' . $config['database'] . '`;'
            )) {
            if (true === $hasOutput) {
                $this->getOutput()->writeln('start importing database ' . $config['database']);
            }
            /**
             * give command as string not array into process,
             * because array will be handled with exec command. exec crashes the import.
             */
            $importProcess = new Process(
                "zcat " . $config['localPath'] .
                '| mysql -h' . $dbDefaultConfig['host'] . ' -u' . $dbDefaultConfig['username'] .
                ' -p' . $dbDefaultConfig['password'] . ' ' . $config['database']
                ,
                null,
                null,
                null,
                (60 * 60)
            );

            $result = 0 === $importProcess->run() && 0 === $importProcess->wait();

            if (true === $hasOutput) {
                if (true === $result) {
                    $message = 'importing database finished successfully';
                } else {
                    $message = $importProcess->getErrorOutput();
                }

                $this->getOutput()->writeln($message);
            }
        } elseif (true === $hasOutput) {
            $this->getOutput()->writeln('recreating database ' . $config['database'] . ' failed. 
                Try to import ' . $config['localPath'] . ' manually!');
        }

        return $result;
    }

    protected function runSSHCommand(
        string $connectionName,
        Connection $sshConn,
        array $commands,
        int $retryAmount = 1
    ): void {
        try {
            $sshConn->run(
                $commands,
                function (string $line) {
                    if ($this->hasOutput()) {
                        $output = $this->getOutput();
                        $output->writeln($line);
                    }
                }
            );
        } catch (\Throwable $e) {
            if ($retryAmount !== 0) {
                $retryAmount--;
                $sshConnNew = $this->getSshManager()->connection($connectionName);
                $this->runSSHCommand($connectionName, $sshConnNew, $commands, $retryAmount);
            } else {
                app('log')->error($e->getMessage(), [
                    'exception' => $e,
                ]);
            }
        }
    }
}