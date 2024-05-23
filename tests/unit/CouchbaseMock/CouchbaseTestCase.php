<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseAccessLayer\Test\unit\CouchbaseMock;

use BowlOfSoup\CouchbaseAccessLayer\Test\unit\CouchbaseMock\CouchbaseMock;
use Couchbase\Bucket;
use Couchbase\ClassicAuthenticator;
use Couchbase\Cluster;
use Couchbase\ClusterOptions;
use Couchbase\PasswordAuthenticator;
use PHPUnit\Framework\TestCase;

/**
 * Extending this abstract will create a dummy Couchbase instance that behaves as an actual Couchbase instance.
 * You're able to get a bucket and do actions like upsert and get. N1ql queries and indexes are not supported!
 *
 * Downloads a 'CouchbaseMock.jar' (~3.8 MB) onto your system.
 */
abstract class CouchbaseTestCase extends TestCase
{
    /** @var string */
    protected $testConnectionString;

    /** @var \Couchbase\PasswordAuthenticator */
    protected $testAuthenticator;

    /** @var string */
    protected $testBucket;

    /** @var string */
    protected $testAdminUser;

    /** @var string */
    protected $testAdminPassword;

    /** @var string */
    protected $testUser;

    /** @var string */
    protected $testPassword;

    /** @var \BowlOfSoup\CouchbaseAccessLayer\Test\unit\CouchbaseMock\CouchbaseMock */
    protected $mock = null;

    /**
     * @throws \Exception
     */
    protected function setUp(): void
    {
        ini_set('couchbase.log_level', 'debug');
        
        $this->mock = new CouchbaseMock();
        $this->mock->start();
        $this->mock->setCccp(true);

        $this->testConnectionString = $this->mock->connectionString();

        $this->testBucket = 'default';
        $this->testAdminUser = 'Administrator';
        $this->testAdminPassword = 'password';
        $this->testUser = 'default';
        $this->testPassword = 'testtest';

        $this->testAuthenticator = new PasswordAuthenticator($this->testUser, $this->testPassword);
    }

    protected function tearDown(): void
    {
        $this->mock->stop();
    }

    /**
     * @return float
     */
    protected function serverVersion()
    {
        $version = getenv('CB_VERSION');
        if ($version === false) {
            $version = '4.6';
        }

        return floatval($version);
    }

    /**
     * @return \Couchbase\Bucket
     */
    protected function getDefaultBucket(): Bucket
    {
        $options = new ClusterOptions();
        $options->credentials($this->testAdminUser, $this->testAdminPassword);

        $cluster = new Cluster($this->testConnectionString, $options);
        $bucket = $cluster->bucket($this->testBucket);

        return $bucket;
    }
}
