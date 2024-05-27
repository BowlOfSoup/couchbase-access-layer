<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseAccessLayer\Builder;

use BowlOfSoup\CouchbaseAccessLayer\Model\Query;

class QueryBuilder
{
    private Query $query;

    private array $parameters = [];

    public function __construct(?string $bucketName = null)
    {
        $this->query = new Query();
        if (null !== $bucketName) {
            $this->query->setFrom($bucketName);
        }
    }

    public function select(string $select, bool $distinct = false): self
    {
        $this->query->addSelect($select, $distinct);

        return $this;
    }

    public function selectMultiple(array $selects): self
    {
        foreach ($selects as $select) {
            $this->query->addSelect($select);
        }

        return $this;
    }

    public function selectRaw(): self
    {
        $this->query->setSelectRaw(true);

        return $this;
    }

    public function from(string $from, ?string $alias = null): self
    {
        $this->query->setFrom($from, $alias);

        return $this;
    }

    /**
     * Because we can't get a parsed query (with parameters) the parameters for the sub query need to be on the 'master' query builder.
     *
     * @throws \BowlOfSoup\CouchbaseAccessLayer\Exception\CouchbaseQueryException
     */
    public function fromSubQuery(QueryBuilder $queryBuilder, string $alias): self
    {
        $this->query->setFromWithSubQuery($queryBuilder, $alias);

        return $this;
    }

    public function where(string $where): self
    {
        $this->query->addWhere($where);

        return $this;
    }

    public function whereOr(string $where): self
    {
        $this->query->addWhereOr($where);

        return $this;
    }

    public function orderBy(string $orderBy, ?string $direction = null): self
    {
        $this->query->addOrderBy($orderBy, $direction);

        return $this;
    }

    public function groupBy(string $groupBy): self
    {
        $this->query->addGroupBy($groupBy);

        return $this;
    }

    /**
     * @param string|int $limit
     */
    public function limit($limit): self
    {
        $this->query->setLimit((int) $limit);

        return $this;
    }

    /**
     * @param string|int $offset
     */
    public function offset($offset): self
    {
        $this->query->setOffset((int) $offset);

        return $this;
    }

    public function useIndex(string $index): self
    {
        $this->query->setUseIndex($index);

        return $this;
    }

    public function useKey(string $key): self
    {
        $this->query->addUseKey($key);

        return $this;
    }

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
     */
    public function setParameter(string $parameter, $content): self
    {
        $this->parameters[$parameter] = $content;

        return $this;
    }

    public function setParameters(array $parameters): self
    {
        $this->parameters = array_merge($this->parameters, $parameters);

        return $this;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @throws \BowlOfSoup\CouchbaseAccessLayer\Exception\CouchbaseQueryException
     */
    public function getQuery(): string
    {
        return $this->query->build();
    }
}
