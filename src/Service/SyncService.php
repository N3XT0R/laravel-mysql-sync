<?php

namespace N3XT0R\MysqlSync;

use Collective\Remote\RemoteManager;
use Illuminate\Config\Repository;

class SyncService
{

    protected $sshManager;
    protected $config;
    protected $storagePath = '';


    public function __construct(RemoteManager $sshManager, Repository $config, string $storagePath)
    {
        $this->setSshManager($sshManager);
        $this->setConfig($config);
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
        $databases = $config->get('mysql-sync.environments.' . $environment . '.databases');

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
        $result = false;
        $sshManager = $this->getSshManager();
        $connectionConfig = $this->getPreparedConnectionConfig($environment);
        $storagePath = $this->getStoragePath();

        foreach ($connectionConfig as $connection => $configs) {
            $sshConn = $sshManager->connection($connection);

            foreach ($configs as $config) {
                $tmpName = $config['db'] . '_' . date('d_m_y_h_i_s') . '.sql';
                $remotePath = '/tmp/' . $tmpName;
                $sshConn->run([
                    "mysqldump -h{$config['host']} -u{$config['user']} -p{$config['password']} {$config['db']} | sed -e 's/DEFINER[ ]*=[ ]*[^*]*\*/\*/' > " . $remotePath
                ]);
                $sshConn->get($remotePath, $storagePath . DIRECTORY_SEPARATOR . $tmpName);
            }
        }


        return $result;
    }

}