<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseAccessLayer\Model;

use BowlOfSoup\CouchbaseAccessLayer\Exception\CouchbaseQueryException;

class Query
{
    const ORDER_ASC = 'ASC';
    const ORDER_DESC = 'DESC';

    /** @var array */
    private $select = [];

    /** @var string */
    private $from;

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
     */
    public function addSelect(string $select)
    {
        $this->select[] = $select;
    }

    /**
     * @param string $from
     *
     * @return \BowlOfSoup\CouchbaseAccessLayer\Model\Query
     */
    public function setFrom(string $from): self
    {
        $this->from = $from;

        return $this;
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
        $select = implode(', ', $this->select);
        if (empty($select)) {
            $select = '*';
        }

        if (null === $this->from) {
            throw new CouchbaseQueryException('Can\'t build N1QL query because of missing \'FROM\'.');
        }

        $query = sprintf('SELECT %s FROM `%s` ', $select, $this->from);

        if (!empty($this->where)) {
            $query .= 'WHERE ' . implode(' AND ', $this->where);
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
