<?php

namespace N3XT0R\MysqlSync;

use Collective\Remote\RemoteManager;
use Illuminate\Config\Repository;

class SyncService
{

    protected $sshManager;
    protected $config;


    public function __construct(RemoteManager $sshManager, Repository $config)
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

        foreach ($connectionConfig as $connection => $configs) {
            $sshConn = $sshManager->connection($connection);

            foreach ($configs as $config) {
                $tmpName = '/tmp/' . $config['db'] . '_' . date('d_m_y_h_i_s') . '.sql';
                $sshConn->run([
                    "mysqldump -h -u -p |  sed -e 's/DEFINER[ ]*=[ ]*[^*]*\*/\*/' > " . $tmpName
                ]);
            }
        }


        return $result;
    }

}