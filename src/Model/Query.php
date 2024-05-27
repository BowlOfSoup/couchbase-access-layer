<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseAccessLayer\Model;

use BowlOfSoup\CouchbaseAccessLayer\Builder\QueryBuilder;
use BowlOfSoup\CouchbaseAccessLayer\Exception\CouchbaseQueryException;

class Query
{
    const ORDER_ASC = 'ASC';
    const ORDER_DESC = 'DESC';
    const DISTINCT = true;

    private array $select = [];

    private bool $selectRaw = false;

    private ?string $from = null;

    private array $useKeys = [];

    private string $useIndex = '';

    private array $whereAnd = [];

    private array $whereOr = [];

    private array $orderBy = [];

    private array $groupBy = [];

    private ?int $limit = null;

    private ?int $offset = null;

    private array $allowedOrderByDirections = ['ASC', 'DESC'];

    public function addSelect(string $select, bool $distinct = false)
    {
        $this->select[] = trim(sprintf('%s %s', true === $distinct ? 'DISTINCT' : '', $select));
    }

    public function setSelectRaw(bool $selectRaw): void
    {
        $this->selectRaw = $selectRaw;
    }

    public function setFrom(string $from, ?string $alias = null): void
    {
        $from = "`{$from}`";
        $this->from = null !== $alias ? "{$from} {$alias}" : $from;
    }

    /**
     * @throws \BowlOfSoup\CouchbaseAccessLayer\Exception\CouchbaseQueryException
     */
    public function setFromWithSubQuery(QueryBuilder $queryBuilder, string $alias): void
    {
        $this->from = "({$queryBuilder->getQuery()}) {$alias}";
    }

    public function addUseKey(string $key): void
    {
        $this->useKeys[] = $key;
    }

    public function setUseIndex(string $index): void
    {
        $this->useIndex = $index;
    }

    public function addWhere(string $where): void
    {
        $this->whereAnd[] = $where;
    }

    public function addWhereOr(string $where): void
    {
        $this->whereOr[] = $where;
    }

    public function addOrderBy(string $orderBy, ?string $direction = null): void
    {
        if (null === $direction || !in_array($direction, $this->allowedOrderByDirections)) {
            $direction = static::ORDER_ASC;
        }

        $this->orderBy[$orderBy] = $direction;
    }

    public function addGroupBy(string $groupBy): void
    {
        $this->groupBy[] = $groupBy;
    }

    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
    }

    public function setOffset(int $offset): void
    {
        $this->offset = $offset;
    }

    /**
     * @throws \BowlOfSoup\CouchbaseAccessLayer\Exception\CouchbaseQueryException
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

        if (!empty($this->useKeys)) {
            $useKeyString = '';
            foreach ($this->useKeys as $useKey) {
                $useKeyString .= sprintf('"%s", ', $useKey);
            }
            $useKeyString = rtrim($useKeyString, ', ');

            $query .= sprintf('USE KEYS [%s]', $useKeyString);

            // Using keys is the end of a query.
            return trim($query);
        }

        if (!empty($this->useIndex)) {
            $query .= sprintf('USE INDEX (%s USING GSI) ', $this->useIndex);
        }

        if (!empty($this->whereAnd) || !empty($this->whereOr)) {
            $whereOrStatement = !empty($this->whereOr) ? ' OR ' : '';

            $query .= 'WHERE ' . implode(' AND ', $this->whereAnd) . $whereOrStatement . implode(' OR ', $this->whereOr);
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
