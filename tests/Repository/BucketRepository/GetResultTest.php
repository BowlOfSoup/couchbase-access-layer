<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseAccessLayer\Test\Repository\BucketRepository;

use BowlOfSoup\CouchbaseAccessLayer\Exception\CouchbaseQueryException;
use BowlOfSoup\CouchbaseAccessLayer\Factory\ClusterFactory;
use BowlOfSoup\CouchbaseAccessLayer\Model\Result;
use BowlOfSoup\CouchbaseAccessLayer\Repository\BucketRepository;
use Couchbase\Bucket;
use Couchbase\Cluster;
use Couchbase\QueryResult;
use PHPUnit\Framework\MockObject\MockObject;

class GetResultTestBaseClass extends AbstractTestBaseClass
{
    public function testGetAResultInAResultModelConsistingOfMultipleDocuments(): void
    {
        $queryBuilder = $this->bucketRepository->createQueryBuilder();

        $bucketMock = $this->createBucketMock(
            function () {
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
            });

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

    public function testGetAResultWithANonArrayResult(): void
    {
        $queryBuilder = $this->bucketRepository->createQueryBuilder();

        $bucketMock = $this->createBucketMock(
            function () {
                return 'foo';
            }
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

//    public function testGetAResultWithASingleValueInARowSyntax(): void
//    {
//        $queryBuilder = $this->bucketRepository->createQueryBuilder();
//
//        $clusterMock = $this
//            ->getMockBuilder(Cluster::class)
//            ->onlyMethods(['bucket', 'query'])
//            ->disableOriginalConstructor()
//            ->getMock();
//        $clusterMock
//            ->expects($this->once())
//            ->method('bucket')
//            ->with('default')
//            ->willReturn($this->bucket);
//        $queryResult = $this
//            ->getMockBuilder(QueryResult::class)
//            ->onlyMethods(['rows'])
//            ->disableOriginalConstructor()
//            ->getMock();
//        $queryResult->method('rows')->willReturn(['foo']);
//        $clusterMock
//            ->expects($this->once())
//            ->method('query')
//            ->willReturn($queryResult);
//
//
//
//
//        $bucketRepository = new BucketRepository('default', $this->getClusterFactory());
//
//        try {
//            $result = $bucketRepository->getResult($queryBuilder);
//
//            $this->assertInstanceOf(Result::class, $result);
//            $this->assertSame(1, $result->getCount());
//            $this->assertSame(1, $result->getTotalCount());
//            $this->assertSame(['foo'], $result->get());
//        } catch (CouchbaseQueryException $e) {
//            trigger_error($e->getMessage(), E_USER_ERROR);
//        }
//    }

    public function testGetQueryResultAsArray(): void
    {
        $bucketRepository = new BucketRepository('default', $this->getClusterFactory());
        $bucketRepository->upsert('1', [
            [
                'something' => 'or nothing',
                'key' => 'value',
            ],
            [
                'foo' => 'bar',
            ],
        ]);
        $bucketRepository->upsert('2', [
            'hello' => 'world',
        ]);

        $queryBuilder = $this->bucketRepository->createQueryBuilder();

        $result = $bucketRepository->getResultAsArray($queryBuilder);

        $bucketRepository->remove('1');
        $bucketRepository->remove('2');

        $this->assertEquals([
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



    }
}
