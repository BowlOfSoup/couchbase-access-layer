<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseAccessLayer\Test\unit\Repository\BucketRepository;

use BowlOfSoup\CouchbaseAccessLayer\Exception\CouchbaseQueryException;
use BowlOfSoup\CouchbaseAccessLayer\Model\Result;
use BowlOfSoup\CouchbaseAccessLayer\Repository\BucketRepository;
use BowlOfSoup\CouchbaseAccessLayer\Test\AbstractTest;

class GetResultTest extends AbstractTest
{
    public function testGetAResultInAResultModelConsistingOfMultipleDocuments(): void
    {
        $queryBuilder = $this->bucketRepository->createQueryBuilder();

        $this->bucketRepository->upsert('1',[ // document 1
            [
                'something' => 'or nothing',
                'key' => 'value',
            ],
            [
                'foo' => 'bar',
            ],
        ]);
        $this->bucketRepository->upsert('2', [ // document 2
            [
                'hello' => 'world',
            ]
        ]);

            $result = $this->bucketRepository->getResult($queryBuilder);

            $this->bucketRepository->remove('1');
            $this->bucketRepository->remove('2');

            $this->assertInstanceOf(Result::class, $result);
            // getCount and getTotalCount are null if no limit and offset are given in N1ql.
            $this->assertSame(2, $result->getCount());
            $this->assertSame(2, $result->getTotalCount());

            $this->assertContains([
                [
                    'key' => 'value',
                    'something' => 'or nothing',
                ],
                [
                    'foo' => 'bar',
                ],
            ], $result->get());
        $this->assertContains([
            [
                'hello' => 'world',
            ],
        ], $result->get());

    }

    public function testGetAResultWithANonArrayResult(): void
    {
        $bucketRepository = new BucketRepository('default', $this->getClusterFactory());
        $bucketRepository->insert('1', 'foo');

        $queryBuilder = $this->bucketRepository->createQueryBuilder();

        try {
            $result = $bucketRepository->getResult($queryBuilder);

            $bucketRepository->remove('1');

            $this->assertInstanceOf(Result::class, $result);
            $this->assertSame(1, $result->getCount());
            $this->assertSame(1, $result->getTotalCount());
            $this->assertSame(['foo'], $result->get());
        } catch (CouchbaseQueryException $e) {
            trigger_error($e->getMessage(), E_USER_ERROR);
        }
    }

    public function testGetQueryResultAsArray(): void
    {
        $bucketRepository = new BucketRepository('default', $this->getClusterFactory());
        $bucketRepository->insert('1', [
            [
                'something' => 'or nothing',
                'key' => 'value',
            ],
            [
                'foo' => 'bar',
            ],
        ]);
        $bucketRepository->insert('2', [
            'hello' => 'world',
        ]);

        $queryBuilder = $this->bucketRepository->createQueryBuilder();
        $queryBuilder->select('*')->from('default');

        $result = $bucketRepository->getResultAsArray($queryBuilder);
        $bucketRepository->remove('1');
        $bucketRepository->remove('2');

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
}
