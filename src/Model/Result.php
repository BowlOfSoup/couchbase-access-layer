<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseAccessLayer\Model;

class Result implements \Iterator, \ArrayAccess, \Countable, \JsonSerializable
{
    /** @var array */
    private $container;

    /** @var int */
    private $count;

    /** @var int */
    private $totalCount;

    /** @var int */
    private $position = 0;

    /**
     * @param array $result
     */
    public function __construct(array $result)
    {
        $this->container = $result;
        $this->position = 0;
    }

    /**
     * @return array
     */
    public function get(): array
    {
        return $this->container;
    }

    /**
     * @return int|null
     */
    public function getCount(): ?int
    {
        return $this->count;
    }

    /**
     * @param int $count
     *
     * @return \BowlOfSoup\CouchbaseAccessLayer\Model\Result
     */
    public function setCount(int $count): Result
    {
        $this->count = $count;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getTotalCount()
    {
        return $this->totalCount;
    }

    /**
     * @param int $totalCount
     *
     * @return \BowlOfSoup\CouchbaseAccessLayer\Model\Result
     */
    public function setTotalCount(int $totalCount): Result
    {
        $this->totalCount = $totalCount;

        return $this;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function current(): mixed
    {
        return $this->container[$this->position];
    }

    public function key(): mixed
    {
        return $this->position;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function valid(): bool
    {
        return isset($this->container[$this->position]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        return isset($this->container[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset): void
    {
        unset($this->container[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset): mixed
    {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }

    public function getFirstResult(): mixed
    {
        return reset($this->container);
    }

    public function count(): int
    {
        return count($this->container);
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): mixed
    {
        return $this->container;
    }
}
