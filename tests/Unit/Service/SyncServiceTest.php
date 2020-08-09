<?php

namespace Tests\Unit\Service;

use Collective\Remote\RemoteManager;
use Illuminate\Config\Repository;
use N3XT0R\MySqlSync\Service\SyncService;
use Tests\DbTestCase;

class SyncServiceTest extends DbTestCase
{
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SyncService(new RemoteManager($this->app), new Repository(), '');
    }


    public function testSetGetStoragePathAreSame(): void
    {
        $storagePath = uniqid('test', true);
        $this->service->setStoragePath($storagePath);
        self::assertSame($storagePath, $this->service->getStoragePath());
    }
}