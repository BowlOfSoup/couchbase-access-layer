<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseAccessLayer\Builder;

use BowlOfSoup\CouchbaseAccessLayer\Model\Query;

class QueryBuilder
{
    const RESULT_AS_ARRAY = true;

    /** @var \BowlOfSoup\CouchbaseAccessLayer\Model\Query */
    private $query;

    /** @var array */
    private $parameters = [];

    /**
     * @param string|null $bucketName
     */
    public function __construct(string $bucketName = null)
    {
        $this->query = new Query();
        if (null !== $bucketName) {
            $this->query->setFrom($bucketName);
        }
    }

    /**
     * @param string $select
     * @param bool $distinct
     *
     * @return \BowlOfSoup\CouchbaseAccessLayer\Builder\QueryBuilder
     */
    public function select(string $select, bool $distinct = false): self
    {
        $this->query->addSelect($select, $distinct);

        return $this;
    }

    /**
     * @param array $selects
     *
     * @return \BowlOfSoup\CouchbaseAccessLayer\Builder\QueryBuilder
     */
    public function selectMultiple(array $selects): self
    {
        foreach ($selects as $select) {
            $this->query->addSelect($select);
        }

        return $this;
    }

    /**
     * @return \BowlOfSoup\CouchbaseAccessLayer\Builder\QueryBuilder
     */
    public function selectRaw(): self
    {
        $this->query->setSelectRaw(true);

        return $this;
    }

    /**
     * @param string $from
     * @param string|null $alias
     *
     * @return \BowlOfSoup\CouchbaseAccessLayer\Builder\QueryBuilder
     */
    public function from(string $from, string $alias = null): self
    {
        $this->query->setFrom($from, $alias);

        return $this;
    }

    /**
     * Because we can't get a parsed query (with parameters) the parameters for the sub query need to be on the 'master' query builder.
     *
     * @param \BowlOfSoup\CouchbaseAccessLayer\Builder\QueryBuilder $queryBuilder
     * @param string $alias
     *
     * @throws \BowlOfSoup\CouchbaseAccessLayer\Exception\CouchbaseQueryException
     *
     * @return \BowlOfSoup\CouchbaseAccessLayer\Builder\QueryBuilder
     */
    public function fromSubQuery(QueryBuilder $queryBuilder, string $alias): self
    {
        $this->query->setFromWithSubQuery($queryBuilder, $alias);

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
     * @param string $index
     *
     * @return \BowlOfSoup\CouchbaseAccessLayer\Builder\QueryBuilder
     */
    public function useIndex(string $index): self
    {
        $this->query->setUseIndex($index);

        return $this;
    }

    /**
     * @param string $key
     *
     * @return \BowlOfSoup\CouchbaseAccessLayer\Builder\QueryBuilder
     */
    public function useKey(string $key): self
    {
        $this->query->addUseKey($key);

        return $this;
    }

    /**
     * @param array $keys
     *
     * @return \BowlOfSoup\CouchbaseAccessLayer\Builder\QueryBuilder
     */
    public function useKeys(array $keys): self
    {
        foreach ($keys as $key) {
            $this->query->addUseKey($key);
        }

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
