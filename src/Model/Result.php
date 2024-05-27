<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseAccessLayer\Model;

class Result implements \Iterator, \ArrayAccess, \Countable, \JsonSerializable
{
    private array $container;

    private ?int $count = null;

    private ?int $totalCount = null;

    private int $position = 0;

    public function __construct(array $result)
    {
        $this->container = $result;
        $this->position = 0;
    }

    public function get(): array
    {
        return $this->container;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function setCount(int $count): Result
    {
        $this->count = $count;

        return $this;
    }

    public function getTotalCount(): ?int
    {
        return $this->totalCount;
    }

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

    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    public function offsetExists($offset): bool
    {
        return isset($this->container[$offset]);
    }

    public function offsetUnset($offset): void
    {
        unset($this->container[$offset]);
    }

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

    public function jsonSerialize(): array
    {
        return $this->container;
    }
}
