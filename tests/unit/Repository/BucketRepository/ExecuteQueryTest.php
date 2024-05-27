<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseAccessLayer\Test\unit\Repository\BucketRepository;

use BowlOfSoup\CouchbaseAccessLayer\Repository\BucketRepository;
use BowlOfSoup\CouchbaseAccessLayer\Test\AbstractTest;

class ExecuteQueryTest extends AbstractTest
{
    public function testExecuteAQueryWithNoResult(): void
    {
        $this->assertSame([], $this->bucketRepository->executeQuery('SELECT * FROM default where param=$some', ['some' => 'parameters']));
    }

    public function testExecuteAQueryWithASingleValueAndNoRowSyntaxInTheQueryResult(): void
    {
        $this->bucketRepository->upsert('1', 'foo');

        $this->assertSame(['foo'], $this->bucketRepository->executeQuery('SELECT * from default'));

        $this->bucketRepository->remove('1');
    }

    public function testExecuteAQueryWithASingleValue(): void
    {
        $this->bucketRepository->upsert('1', ['foo']);

        $this->assertSame([[0 => 'foo']], $this->bucketRepository->executeQuery('SELECT * FROM default'));

        $this->bucketRepository->remove('1');
    }

    public function testExecuteAQueryWithResultConsistingOfMultipleDocuments(): void
    {
        $this->bucketRepository->upsert('1', [
                [
                'something' => 'or nothing',
                'key' => 'value',
                ],
                [
                    'foo' => 'bar',
                ]
            ]
        );
        $this->bucketRepository->upsert('2',  [
            'hello' => 'world',
        ]);

        $result = $this->bucketRepository->executeQuery('SELECT * FROM default');

        $this->bucketRepository->remove('1');
        $this->bucketRepository->remove('2');

        $this->assertContains([
            [
                'key' => 'value',
                'something' => 'or nothing',
            ],
            [
                'foo' => 'bar',
            ],
        ], $result);

        $this->assertContains([
            'hello' => 'world',
        ], $result);
    }

    public function testExecuteAQueryWithResultIncludingBucketName(): void
    {
        $extraBucketRepository = new BucketRepository('someOtherBucket', $this->getClusterFactory());

        $this->bucketRepository->upsert('1', [
            'hello' => 'world',
        ]);

        $extraBucketRepository->upsert('2', [
            'foo' => 'bar',
        ]);

        $result = $this->bucketRepository->executeQuery('SELECT * FROM default');

        $this->bucketRepository->remove('1');
        $extraBucketRepository->remove('2');

        $this->assertSame(
            [
                [
                    'hello' => 'world',
                ],
            ],
            $result
        );
    }

    public function testExecuteAQueryAndReturnOnlyTheFirstResult(): void
    {
        $this->bucketRepository->upsert('1', ['field' => 'foo']);
        $this->bucketRepository->upsert('2', ['field' => 'bar']);

        $result = $this->bucketRepository->executeQueryWithOneResult('SELECT * FROM default ORDER BY field ASC');

        $this->bucketRepository->remove('1');
        $this->bucketRepository->remove('2');

        $this->assertSame(['field' => 'bar'], $result);
    }
}
