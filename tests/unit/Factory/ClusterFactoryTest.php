<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseAccessLayer\Test\unit\Factory;

use BowlOfSoup\CouchbaseAccessLayer\Factory\ClusterFactory;
use BowlOfSoup\CouchbaseAccessLayer\Test\unit\CouchbaseMock\CouchbaseTestCase;
use Couchbase\Cluster;
use PHPUnit\Framework\TestCase;

class ClusterFactoryTest extends CouchbaseTestCase
{
    /**
     * @throws \PHPUnit\Framework\Exception
     */
    public function testClusterFactoryCreatesACluster(): void
    {
        $parts = explode(',', $this->testConnectionString);
        $factory = new ClusterFactory($parts[0], $this->testAdminUser, $this->testAdminPassword);
        $connection = $factory->create();

        $this->assertInstanceOf(Cluster::class, $connection);
    }
}
