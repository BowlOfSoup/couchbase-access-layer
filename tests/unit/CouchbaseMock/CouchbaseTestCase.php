<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseAccessLayer\Test\unit\CouchbaseMock;

use Couchbase\Bucket;
use Couchbase\Cluster;
use Couchbase\ClusterOptions;
use Couchbase\PasswordAuthenticator;
use PHPUnit\Framework\TestCase;

/**
 * Extending this abstract will give access to the Couchbase instance in the Docker setup
 */
abstract class CouchbaseTestCase extends TestCase
{
    protected string $testConnectionString = 'couchbase';

    protected PasswordAuthenticator $testAuthenticator;

    protected string $testBucket;

    protected string $testAdminUser;

    protected string $testAdminPassword;

    protected string $testUser;

    protected string $testPassword;

    /**
     * @throws \Exception
     */
    protected function setUp(): void
    {
        ini_set('couchbase.log_level', 'debug');

        $this->testBucket = 'default';
        $this->testAdminUser = 'Administrator';
        $this->testAdminPassword = 'password';
        $this->testUser = 'default';
        $this->testPassword = 'testtest';

        $this->testAuthenticator = new PasswordAuthenticator($this->testUser, $this->testPassword);
    }

    protected function getDefaultBucket(): Bucket
    {
        $options = new ClusterOptions();
        $options->credentials($this->testUser, $this->testPassword);

        $cluster = new Cluster($this->testConnectionString, $options);

        return $cluster->bucket($this->testBucket);
    }
}
