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


    public function sync(string $environment): bool
    {
        $result = false;
        $sshManager = $this->getSshManager();
        $config = $this->getConfig();
        $environmentConfig = $config->get('mysql-sync.environments.' . $environment);
        $sshManager->connection($environment);

        return $result;
    }

}