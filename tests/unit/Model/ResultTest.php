<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseAccessLayer\Test\unit\Model;

use BowlOfSoup\CouchbaseAccessLayer\Model\Result;
use PHPUnit\Framework\TestCase;

class ResultTest extends TestCase
{
    public function testConstructionWorksAndGetMethodReturnsArray(): void
    {
        $array = [
            0 => 'test',
            1 => 'another-test',
        ];

        $result = new Result($array);

        self::assertSame($array, $result->get());
    }

    public function testSetCountSetsAndGetCountReturnsCorrectAmount(): void
    {
        $array = [
            0 => 'test',
            1 => 'another-test',
        ];

        $result = new Result($array);

        $result->setCount(2);

        self::assertSame(2, $result->getCount());
    }

    public function testSetTotalCountSetsAndGetTotalCountReturnsCorrectAmount(): void
    {
        $array = [
            0 => 'test',
            1 => 'another-test',
        ];

        $result = new Result($array);

        $result->setTotalCount(1337);

        self::assertSame(1337, $result->getTotalCount());
    }

    public function testPositionStartsAtZero(): void
    {
        $array = [
            0 => 'test',
            1 => 'another-test',
        ];

        $result = new Result($array);

        self::assertSame(0, $result->key());
    }

    public function testCurrentReturnsCorrectValue(): void
    {
        $array = [
            0 => 'test',
            1 => 'another-test',
        ];

        $result = new Result($array);

        self::assertSame('test', $result->current());
        $result->next();
        self::assertSame('another-test', $result->current());
    }

    public function testValidReturnsCorrectValue(): void
    {
        $array = [
            0 => 'test',
            1 => 'another-test',
        ];

        $result = new Result($array);

        self::assertTrue($result->valid());
        $result->next();
        self::assertTrue($result->valid());
        $result->next();
        self::assertFalse($result->valid());
    }

    public function testSettingAValueWorks(): void
    {
        $array = [
            0 => 'test',
            1 => 'another-test',
        ];

        $result = new Result($array);

        self::assertSame('test', $result[0]);
        $result[0] = 'something else';
        self::assertSame('something else', $result[0]);

        $result[] = 'third entry at index 2';

        self::assertSame('third entry at index 2', $result[2]);
    }

    public function testOffsetExistsReturnsCorrectValue(): void
    {
        $array = [
            0 => 'test',
            1 => 'another-test',
        ];

        $result = new Result($array);

        self::assertTrue($result->offsetExists(0));
        self::assertFalse($result->offsetExists(2));
    }

    public function testOffsetUnsetRemovesEntry(): void
    {
        $array = [
            0 => 'test',
            1 => 'another-test',
        ];

        $result = new Result($array);

        $result->offsetUnset(0);

        self::assertFalse($result->offsetExists(0));
    }

    public function testOffsetGetReturnsCorrectValue(): void
    {
        $array = [
            0 => 'test',
            1 => 'another-test',
        ];

        $result = new Result($array);

        self::assertSame('test', $result[0]);
        self::assertSame('test', $result->offsetGet(0));
    }

    public function testGetFirstResultReturnsFirstResult(): void
    {
        $array = [
            0 => 'test',
            1 => 'another-test',
        ];

        $result = new Result($array);

        self::assertSame('test', $result->getFirstResult());
    }

    public function testCountReturnsCorrectAmount(): void
    {
        $array = [
            0 => 'test',
            1 => 'another-test',
        ];

        $result = new Result($array);

        self::assertSame(2, $result->count());
    }

    public function testJsonSerializeReturnsArray(): void
    {
        $array = [
            0 => 'test',
            1 => 'another-test',
        ];

        $result = new Result($array);

        self::assertIsArray($result->jsonSerialize());
        self::assertSame($array, $result->jsonSerialize());
    }

    public function testRewindResetsThePosition(): void
    {
        $array = [
            0 => 'test',
            1 => 'another-test',
        ];

        $result = new Result($array);

        $result->next();

        self::assertSame(1, $result->key());

        $result->rewind();

        self::assertSame(0, $result->key());
    }
}