<?php

namespace N3XT0R\MysqlSync;

use Collective\Remote\RemoteManager;

class SyncService
{

    protected $sshManager;


    public function __construct(RemoteManager $sshManager)
    {
        $this->setSshManager($sshManager);
    }

    /**
     * @return RemoteManager
     */
    public function getSshManager(): RemoteManager
    {
        return $this->sshManager;
    }

    /**
     * @param mixed $sshManager
     */
    public function setSshManager(RemoteManager $sshManager): void
    {
        $this->sshManager = $sshManager;
    }

}