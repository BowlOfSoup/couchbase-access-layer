<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseAccessLayer\Test\Repository\BucketRepository;

class ExecuteQueryTest extends AbstractTest
{
    public function testExecuteAQueryWithNoResult(): void
    {
        $bucketMock = $this->createBucketMock(
            $this->returnCallback(function () {
                return null;
            })
        );

        $bucketRepository = $this->replaceBucketOnBucketRepositoryMock($bucketMock);

        $this->assertSame([], $bucketRepository->executeQuery('someQuery', ['some' => 'parameters']));
    }

    public function testExecuteAQueryWithASingleValueAndNoRowSyntaxInTheQueryResult(): void
    {
        $bucketMock = $this->createBucketMock(
            $this->returnCallback(function () {
                return 'foo';
            })
        );

        $bucketRepository = $this->replaceBucketOnBucketRepositoryMock($bucketMock);

        $this->assertSame(['foo'], $bucketRepository->executeQuery('someQuery', ['some' => 'parameters']));
    }

    public function testExecuteAQueryWithASingleValue(): void
    {
        $bucketMock = $this->createBucketMock(
            $this->returnCallback(function () {
                $result = new \stdClass;
                $result->rows = 'foo';

                return $result;
            })
        );

        $bucketRepository = $this->replaceBucketOnBucketRepositoryMock($bucketMock);

        $this->assertSame(['foo'], $bucketRepository->executeQuery('someQuery', ['some' => 'parameters']));
    }

    public function testExecuteAQueryWithResultConsistingOfMultipleDocuments(): void
    {
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
            })
        );

        $bucketRepository = $this->replaceBucketOnBucketRepositoryMock($bucketMock);

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
            $bucketRepository->executeQuery('someQuery', ['some' => 'parameters'])
        );
    }

    public function testExecuteAQueryWithResultIncludingBucketName(): void
    {
        $bucketMock = $this->createBucketMock(
            $this->returnCallback(function () {
                $result = new \stdClass;
                $result->rows = [
                    [ // document 1
                        'someOtherBucketName' => [
                            'foo' => 'bar',
                        ],
                        'default' => [
                            'hello' => 'world',
                        ]
                    ],
                ];

                return $result;
            }));

        $bucketRepository = $this->replaceBucketOnBucketRepositoryMock($bucketMock);

        $this->assertSame(
            [
                [
                    'hello' => 'world',
                ],
            ],
            $bucketRepository->executeQuery('someQuery', ['some' => 'parameters'])
        );
    }

    public function testExecuteAQueryAndReturnOnlyTheFirstResult(): void
    {
        $bucketMock = $this->createBucketMock(
            $this->returnCallback(function () {
                $result = new \stdClass;
                $result->rows = [
                    ['foo'],
                    ['bar'],
                ];

                return $result;
            })
        );

        $bucketRepository = $this->replaceBucketOnBucketRepositoryMock($bucketMock);

        $this->assertSame('foo', $bucketRepository->executeQueryWithOneResult('someQuery', ['some' => 'parameters']));
    }
}
