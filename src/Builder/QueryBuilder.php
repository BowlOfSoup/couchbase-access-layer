<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseAccessLayer\Builder;

use BowlOfSoup\CouchbaseAccessLayer\Model\Query;
use Couchbase\Bucket;

class QueryBuilder
{
    const RESULT_AS_ARRAY = true;

    /** @var \Couchbase\Bucket */
    private $bucket;

    /** @var \BowlOfSoup\CouchbaseAccessLayer\Model\Query */
    private $query;

    /** @var array */
    private $parameters = [];

    /**
     * @param \Couchbase\Bucket $bucket
     */
    public function __construct(Bucket $bucket)
    {
        $this->bucket = $bucket;
        $this->query = (new Query())->setFrom($bucket->getName());
    }

    /**
     * @param string $select
     *
     * @return \BowlOfSoup\CouchbaseAccessLayer\Builder\QueryBuilder
     */
    public function select(string $select): self
    {
        $this->query->addSelect($select);

        return $this;
    }

    /**
     * @param string $where
     *
     * @return \BowlOfSoup\CouchbaseAccessLayer\Builder\QueryBuilder
     */
    public function where(string $where): self
    {
        $this->query->addWhere($where);

        return $this;
    }

    /**
     * @param string $orderBy
     * @param string|null $direction
     *
     * @return \BowlOfSoup\CouchbaseAccessLayer\Builder\QueryBuilder
     */
    public function orderBy(string $orderBy, $direction = null): self
    {
        $this->query->addOrderBy($orderBy, $direction);

        return $this;
    }

    /**
     * @param string $groupBy
     *
     * @return \BowlOfSoup\CouchbaseAccessLayer\Builder\QueryBuilder
     */
    public function groupBy(string $groupBy): self
    {
        $this->query->addGroupBy($groupBy);

        return $this;
    }

    /**
     * @param string|int $limit
     *
     * @return \BowlOfSoup\CouchbaseAccessLayer\Builder\QueryBuilder
     */
    public function limit($limit): self
    {
        $this->query->setLimit((int) $limit);

        return $this;
    }

    /**
     * @param string|int $offset
     *
     * @return \BowlOfSoup\CouchbaseAccessLayer\Builder\QueryBuilder
     */
    public function offset($offset): self
    {
        $this->query->setOffset((int) $offset);

        return $this;
    }

    /**
     * @param string $parameter
     * @param string|int $content
     *
     * @return \BowlOfSoup\CouchbaseAccessLayer\Builder\QueryBuilder
     */
    public function setParameter(string $parameter, $content): self
    {
        $this->parameters[$parameter] = $content;

        return $this;
    }

    /**
     * @param array $parameters
     *
     * @return \BowlOfSoup\CouchbaseAccessLayer\Builder\QueryBuilder
     */
    public function setParameters(array $parameters): self
    {
        $this->parameters = array_merge($this->parameters, $parameters);

        return $this;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @throws \BowlOfSoup\CouchbaseAccessLayer\Exception\CouchbaseQueryException
     *
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query->build();
    }
}
