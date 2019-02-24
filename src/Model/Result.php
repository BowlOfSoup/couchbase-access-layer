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
    public function getCount()
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

    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return $this->container[$this->position];
    }

    /**
     * @return mixed
     */
    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        ++$this->position;
    }

    /**
     * @return mixed
     */
    public function valid()
    {
        return isset($this->container[$this->position]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
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
    public function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->container);
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        return $this->container;
    }
}
