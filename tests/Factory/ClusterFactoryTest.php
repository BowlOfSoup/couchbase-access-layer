<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseAccessLayer\Test\Factory;

use BowlOfSoup\CouchbaseAccessLayer\Factory\ClusterFactory;
use Couchbase\Cluster;
use PHPUnit\Framework\TestCase;

class ClusterFactoryTest extends TestCase
{
    /**
     * @throws \PHPUnit\Framework\Exception
     */
    public function testClusterFactoryCreatesACluster()
    {
        $factory = new ClusterFactory('host', 'user', 'password');
        $connection = $factory->create();

        $this->assertInstanceOf(Cluster::class, $connection);
    }
}
