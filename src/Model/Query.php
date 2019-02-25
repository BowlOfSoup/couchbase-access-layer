<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseAccessLayer\Model;

use BowlOfSoup\CouchbaseAccessLayer\Builder\QueryBuilder;
use BowlOfSoup\CouchbaseAccessLayer\Exception\CouchbaseQueryException;

class Query
{
    const ORDER_ASC = 'ASC';
    const ORDER_DESC = 'DESC';

    /** @var array */
    private $select = [];

    /** @var bool */
    private $distinct = false;

    /** @var bool */
    private $selectRaw = false;

    /** @var string */
    private $from;

    /** @var array */
    private $useKeys;

    /** @var string */
    private $useIndex;

    /** @var array */
    private $where = [];

    /** @var array */
    private $orderBy = [];

    /** @var array */
    private $groupBy = [];

    /** @var int */
    private $limit;

    /** @var int */
    private $offset;

    /** @var array */
    private $allowedOrderByDirections = ['ASC', 'DESC'];

    /**
     * @param string $select
     * @param bool $distinct
     */
    public function addSelect(string $select, bool $distinct)
    {
        $this->select[] = trim(sprintf('%s %s', true === $distinct ? 'DISTINCT ' : '', $select));
    }

    /**
     * @param bool $distinct
     */
    public function setDistinct(bool $distinct)
    {
        $this->distinct = $distinct;
    }

    /**
     * @param bool $selectRaw
     */
    public function setSelectRaw(bool $selectRaw)
    {
        $this->selectRaw = $selectRaw;
    }

    /**
     * @param string|array $from
     * @param string|null $alias
     */
    public function setFrom($from, $alias = null)
    {
        $from = is_array($from) ? $from : "`{$from}`";
        $this->from = null !== $alias ? "{$from} {$alias}" : $from;
    }

    /**
     * @param \BowlOfSoup\CouchbaseAccessLayer\Builder\QueryBuilder $queryBuilder
     * @param string $alias
     *
     * @throws \BowlOfSoup\CouchbaseAccessLayer\Exception\CouchbaseQueryException
     */
    public function setFromWithSubQuery(QueryBuilder $queryBuilder, string $alias)
    {
        $this->from = "({$queryBuilder->getQuery()}) {$alias}";
    }

    /**
     * @param string|array $keys
     */
    public function addUseKey($keys)
    {
        if (!is_array($keys)) {
            $this->useKeys[] = $keys;

            return;
        }

        if (!is_string($keys)) {
            return;
        }

        $this->useKeys = $keys;
    }

    /**
     * @param string $index
     */
    public function setUseIndex(string $index)
    {
        $this->useIndex = $index;
    }

    /**
     * @param string $where
     */
    public function addWhere(string $where)
    {
        $this->where[] = $where;
    }

    /**
     * @param string $orderBy
     * @param string|null $direction
     */
    public function addOrderBy(string $orderBy, string $direction = null)
    {
        if (null === $direction || !in_array($direction, $this->allowedOrderByDirections)) {
            $direction = static::ORDER_ASC;
        }

        $this->orderBy[$orderBy] = $direction;
    }

    /**
     * @param string $groupBy
     */
    public function addGroupBy(string $groupBy)
    {
        $this->groupBy[] = $groupBy;
    }

    /**
     * @param int $limit
     */
    public function setLimit(int $limit)
    {
        $this->limit = $limit;
    }

    /**
     * @param int $offset
     */
    public function setOffset(int $offset)
    {
        $this->offset = $offset;
    }

    /**
     * @throws \BowlOfSoup\CouchbaseAccessLayer\Exception\CouchbaseQueryException
     *
     * @return string
     */
    public function build(): string
    {
        if ($this->selectRaw && (count($this->select) > 1 || empty($this->select))) {
            throw new CouchbaseQueryException("Can only use 'SELECT RAW' when exactly one property is selected.");
        }

        $select = implode(', ', $this->select);
        if (empty($select)) {
            $select = '*';
        }
        if ($this->selectRaw) {
            if (strpos($select, 'DISTINCT') !== false) {
                $select = str_replace('DISTINCT', 'DISTINCT RAW', $select);
            } else {
                $select = 'RAW ' . $select;
            }
        }

        if (null === $this->from) {
            throw new CouchbaseQueryException("Can't build N1QL query because of missing 'FROM'.");
        }

        $query = sprintf('SELECT %s FROM %s ', $select, $this->from);

        if (!empty($this->useIndex)) {
            $query .= sprintf(' USE INDEX (%s USING GSI)'. $this->useIndex);
        }

        if (!empty($this->where)) {
            $query .= 'WHERE ' . implode(' AND ', $this->where);
        }

        if (!empty($this->useKeys)) {
            $useKeyString = '';
            foreach ($this->useKeys as $useKey) {
                $useKeyString .= sprintf('"%s", ', $useKey);
            }
            $useKeyString = rtrim($query, ', ');

            $query .= sprintf(' USE KEYS [%s]'. $useKeyString);

            // Using keys is the end of a query.
            return $query;
        }

        if (!empty($this->groupBy)) {
            $query .= ' GROUP BY ' . implode(', ', $this->groupBy);
        }

        if (!empty($this->orderBy)) {
            $query .= ' ORDER BY ';
            foreach ($this->orderBy as $field => $direction) {
                $query .= sprintf('%s %s, ', $field, $direction);
            }
            $query = rtrim($query, ', ');
        }

        if (null !== $this->limit) {
            $query .= sprintf(' LIMIT %d', $this->limit);
        }

        if (null !== $this->offset) {
            $query .= sprintf(' OFFSET %d ', $this->offset);
        }

        return trim($query);
    }
}
