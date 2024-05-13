<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseAccessLayer\Test\CouchbaseMock;

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

    /** @var \BowlOfSoup\CouchbaseAccessLayer\Test\CouchbaseMock\CouchbaseMock */
    protected $mock = null;

    /**
     * @throws \Exception
     */
    protected function setUp(): void
    {
        $this->mock = new CouchbaseMock();
        $this->mock->start();
        $this->mock->setCccp(true);

        $this->testConnectionString = $this->mock->connectionString();

        $this->testBucket = 'default';
        $this->testAdminUser = 'Administrator';
        $this->testAdminPassword = 'password';
        $this->testUser = 'default';
        $this->testPassword = '';

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
     * @param \Couchbase\Bucket $bucket
     */
    protected function setTimeouts(Bucket $bucket)
    {
        $val = getenv("CB_OPERATION_TIMEOUT");
        if ($val !== false) {
            $bucket->operationTimeout = intval($val);
        } else {
            $bucket->operationTimeout = 5000000;
        }
        $val = getenv("CB_VIEW_TIMEOUT");
        if ($val !== false) {
            $bucket->viewTimeout = intval($val);
        }
        $val = getenv("CB_DURABILITY_INTERVAL");
        if ($val !== false) {
            $bucket->durabilityInterval = intval($val);
        }
        $val = getenv("CB_DURABILITY_TIMEOUT");
        if ($val !== false) {
            $bucket->durabilityTimeout = intval($val);
        }
        $val = getenv("CB_HTTP_TIMEOUT");
        if ($val !== false) {
            $bucket->httpTimeout = intval($val);
        }
        $val = getenv("CB_CONFIG_TIMEOUT");
        if ($val !== false) {
            $bucket->configTimeout = intval($val);
        }
        $val = getenv("CB_CONFIG_DELAY");
        if ($val !== false) {
            $bucket->configDelay = intval($val);
        }
        $val = getenv("CB_CONFIG_NODE_TIMEOUT");
        if ($val !== false) {
            $bucket->configNodeTimeout = intval($val);
        }
        $val = getenv("CB_HTTP_CONFIG_IDLE_TIMEOUT");
        if ($val !== false) {
            $bucket->htconfigIdleTimeout = intval($val);
        }
        if (getenv("REPORT_TIMEOUT_SETTINGS")) {
            printf("\n[TIMEOUTS] OT=%d, VT=%d, DI=%d, DT=%d, HT=%d, CT=%d, CD=%d, CNT=%d, HCIT=%d\n",
                $bucket->operationTimeout,
                $bucket->viewTimeout,
                $bucket->durabilityInterval,
                $bucket->durabilityTimeout,
                $bucket->httpTimeout,
                $bucket->configTimeout,
                $bucket->configDelay,
                $bucket->configNodeTimeout,
                $bucket->htconfigIdleTimeout);
        }
    }

    /**
     * @return \Couchbase\Bucket
     */
    protected function getDefaultBucket(): Bucket
    {
        $options = new ClusterOptions();
        $options->authenticator($this->testAuthenticator);

        $cluster = new Cluster($this->testConnectionString, $options);
        $bucket = $cluster->bucket($this->testBucket);
        $this->setTimeouts($bucket);

        return $bucket;
    }
}
