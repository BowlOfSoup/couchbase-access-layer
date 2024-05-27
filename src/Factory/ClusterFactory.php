<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseAccessLayer\Factory;

use Couchbase\Cluster;
use Couchbase\ClusterInterface;
use Couchbase\ClusterOptions;
use Couchbase\PasswordAuthenticator;

class ClusterFactory
{
    private string $host;

    private string $user;

    private string $password;

    public function __construct(string $host, string $user, string $password)
    {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
    }

    public function create(): ClusterInterface
    {
        $options = new ClusterOptions();
        $options->credentials($this->user, $this->password);

        $cluster = new Cluster('couchbase://' . $this->host, $options);

        return $cluster;
    }
}
