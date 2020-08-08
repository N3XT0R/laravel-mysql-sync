<?php

namespace N3XT0R\MySqlSync\Service;

use Collective\Remote\ConnectionInterface;
use Collective\Remote\RemoteManager;
use Illuminate\Config\Repository;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class SyncService
{

    protected $sshManager;
    protected $config;
    protected $storagePath = '';


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
            ];
        }

        return $preparedConfig;
    }


    public function sync(string $environment): bool
    {
        $result = true;
        $sshManager = $this->getSshManager();
        $connectionConfig = $this->getPreparedConnectionConfig($environment);


        foreach ($connectionConfig as $connection => $configs) {
            $sshConn = $sshManager->connection($connection);

            foreach ($configs as $config) {
                if (false === $this->runDatabaseCopy($sshConn, $config)) {
                    $result = false;
                }
            }
        }


        return $result;
    }


    protected function runDatabaseCopy(ConnectionInterface $sshConn, array $config): bool
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
        if ($adapter->has('dumps')) {
            $adapter->createDir('dumps');
        }
        $tmpName = $config['database'] . '_' . date('d_m_y_h_i_s') . '.sql';
        $remotePath = '/tmp/' . $tmpName;
        $localPath = $storagePath . DIRECTORY_SEPARATOR . 'dumps' . DIRECTORY_SEPARATOR . $tmpName;

        $sshConn->run([
            "mysqldump -h{$config['host']} -u{$config['user']} -p{$config['password']} {$config['database']} | sed -e 's/DEFINER[ ]*=[ ]*[^*]*\*/\*/' > " . $remotePath
        ]);
        $sshConn->get($remotePath, $localPath);

        if ($adapter->has($localPath) &&
            true === DB::connection()->statement(
                'DROP DATABASE IF EXISTS  `' . $config['database'] . '`; CREATE DATABASE `' . $config['database'] . '`;'
            )) {
            $importProcess = new Process([
                'mysql',
                '-h' . $dbDefaultConfig['host'],
                '-u' . $dbDefaultConfig['username'],
                '-p' . $dbDefaultConfig['password'],
                $dbDefaultConfig['database'],
                '<',
                $localPath
            ]);

            $result = 0 === $importProcess->run();
        }

        return $result;
    }

}