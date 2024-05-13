<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseAccessLayer\Factory;

use Couchbase\Cluster;
use Couchbase\ClusterOptions;
use Couchbase\PasswordAuthenticator;

class ClusterFactory
{
    /** @var string */
    private $host;

    /** @var string */
    private $user;

    /** @var string */
    private $password;

    /**
     * @param string $host
     * @param string $user
     * @param string $password
     */
    public function __construct(string $host, string $user, string $password)
    {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * @return \Couchbase\Cluster
     */
    public function create(): Cluster
    {
        $authenticator = new PasswordAuthenticator($this->user, $this->password);

        $options = new ClusterOptions();
        $options->authenticator($authenticator);

        $cluster = new Cluster('couchbase://' . $this->host, $options);

        return $cluster;
    }
}
