<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseAccessLayer\Test\Repository\BucketRepository;

use BowlOfSoup\CouchbaseAccessLayer\Exception\CouchbaseQueryException;
use BowlOfSoup\CouchbaseAccessLayer\Model\Result;

class GetResultTest extends AbstractTest
{
    public function testGetAResultInAResultModelConsistingOfMultipleDocuments()
    {
        $queryBuilder = $this->bucketRepository->createQueryBuilder();

        $bucketMock = $this->createBucketMock(
            $this->returnCallback(function () {
                $result = new \stdClass;
                $result->rows = [
                    [ // document 1
                        [
                            'something' => 'or nothing',
                            'key' => 'value',
                        ],
                        [
                            'foo' => 'bar',
                        ],
                    ],
                    [ // document 2
                        [
                            'hello' => 'world',
                        ]
                    ],
                ];
                $result->metrics = [
                    'resultCount' => 5123,
                    'sortCount' => 453231,
                ];

                return $result;
            }));

        $bucketRepository = $this->replaceBucketOnBucketRepositoryMock($bucketMock);

        try {
            $result = $bucketRepository->getResult($queryBuilder);

            $this->assertInstanceOf(Result::class, $result);
            // getCount and getTotalCount are null if no limit and offset are given in N1ql.
            $this->assertSame(5123, $result->getCount());
            $this->assertSame(453231, $result->getTotalCount());
            $this->assertSame(
                [
                    [
                        [
                            'something' => 'or nothing',
                            'key' => 'value',
                        ],
                        [
                            'foo' => 'bar',
                        ],
                    ],
                    [
                        'hello' => 'world',
                    ],
                ],
                $result->get()
            );
        } catch (CouchbaseQueryException $e) {
            trigger_error($e->getMessage(), E_USER_ERROR);
        }
    }

    public function testGetAResultWithANonArrayResult()
    {
        $queryBuilder = $this->bucketRepository->createQueryBuilder();

        $bucketMock = $this->createBucketMock(
            $this->returnCallback(function () {
                return 'foo';
            })
        );

        $bucketRepository = $this->replaceBucketOnBucketRepositoryMock($bucketMock);

        try {
            $result = $bucketRepository->getResult($queryBuilder);

            $this->assertInstanceOf(Result::class, $result);
            $this->assertSame(1, $result->getCount());
            $this->assertSame(1, $result->getTotalCount());
            $this->assertSame(['foo'], $result->get());
        } catch (CouchbaseQueryException $e) {
            trigger_error($e->getMessage(), E_USER_ERROR);
        }
    }

    public function testGetAResultWithASingleValueInARowSyntax()
    {
        $queryBuilder = $this->bucketRepository->createQueryBuilder();

        $bucketMock = $this->createBucketMock(
            $this->returnCallback(function () {
                $result = new \stdClass;
                $result->rows = 'foo';

                return $result;
            })
        );

        $bucketRepository = $this->replaceBucketOnBucketRepositoryMock($bucketMock);

        try {
            $result = $bucketRepository->getResult($queryBuilder);

            $this->assertInstanceOf(Result::class, $result);
            $this->assertSame(1, $result->getCount());
            $this->assertSame(1, $result->getTotalCount());
            $this->assertSame(['foo'], $result->get());
        } catch (CouchbaseQueryException $e) {
            trigger_error($e->getMessage(), E_USER_ERROR);
        }
    }

    public function testGetQueryResultAsArray()
    {
        $queryBuilder = $this->bucketRepository->createQueryBuilder();

        $bucketMock = $this->createBucketMock(
            $this->returnCallback(function () {
                $result = new \stdClass;
                $result->rows = [
                    [ // document 1
                        [
                            'something' => 'or nothing',
                            'key' => 'value',
                        ],
                        [
                            'foo' => 'bar',
                        ],
                    ],
                    [ // document 2
                        [
                            'hello' => 'world',
                        ]
                    ],
                ];

                return $result;
            }));

        $bucketRepository = $this->replaceBucketOnBucketRepositoryMock($bucketMock);

        try {
            $result = $bucketRepository->getResultAsArray($queryBuilder);

            $this->assertSame(
                [
                    [
                        [
                            'something' => 'or nothing',
                            'key' => 'value',
                        ],
                        [
                            'foo' => 'bar',
                        ],
                    ],
                    [
                        'hello' => 'world',
                    ],
                ],
                $result
            );
        } catch (CouchbaseQueryException $e) {
            trigger_error($e->getMessage(), E_USER_ERROR);
        }
    }
}
