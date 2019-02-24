<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseAccessLayer\Test\Repository;

use BowlOfSoup\CouchbaseAccessLayer\Builder\QueryBuilder;
use BowlOfSoup\CouchbaseAccessLayer\Exception\CouchbaseQueryException;
use BowlOfSoup\CouchbaseAccessLayer\Factory\ClusterFactory;
use BowlOfSoup\CouchbaseAccessLayer\Model\Result;
use BowlOfSoup\CouchbaseAccessLayer\Repository\BucketRepository;
use BowlOfSoup\CouchbaseAccessLayer\Test\CouchbaseMock\CouchbaseTestCase;
use Couchbase\Bucket;
use Couchbase\Cluster;
use Couchbase\N1qlQuery;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;

class BucketRepositoryTest extends CouchbaseTestCase
{
    /** @var \Couchbase\Bucket */
    private $bucket;

    /** @var \BowlOfSoup\CouchbaseAccessLayer\Repository\BucketRepository */
    private $bucketRepository;

    protected function setUp()
    {
        parent::setUp();

        $this->bucket = $this->getDefaultBucket();

        $clusterMock = $this
            ->getMockBuilder(Cluster::class)
            ->setMethods(['openBucket'])
            ->disableOriginalConstructor()
            ->getMock();
        $clusterMock
            ->expects($this->once())
            ->method('openBucket')
            ->with('default', '')
            ->will($this->returnValue($this->bucket));

        /** @var \PHPUnit\Framework\MockObject\MockObject|\BowlOfSoup\CouchbaseAccessLayer\Factory\ClusterFactory $clusterFactoryMock */
        $clusterFactoryMock = $this
            ->getMockBuilder(ClusterFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $clusterFactoryMock
            ->expects($this->once())
            ->method('create')
            ->will($this->returnValue($clusterMock));

        $this->bucketRepository = new BucketRepository('default', $clusterFactoryMock);
    }

    /**
     * Flush bucket and close connection.
     */
    protected function tearDown()
    {
        $this->bucket->manager()->flush();

        parent::tearDown();
    }

    public function testGetBucket()
    {
        $this->assertInstanceOf(Bucket::class, $this->bucketRepository->getBucket());
    }

    public function testGetBucketName()
    {
        $this->assertSame('default', $this->bucketRepository->getBucketName());
    }

    public function testGetDocumentByKey()
    {
        $id = uniqid();
        $value = ['someData' => 'test123', 'foo' => ['somethingsomething']];

        $this->bucket->upsert($id, $value);

        $this->assertSame($value, $this->bucketRepository->getByKey($id));
    }

    public function testExecuteAQueryWithNoResult()
    {
        $bucketMock = $this->createBucketMock(
            $this->returnCallback(function () {
                return null;
            })
        );

        $bucketRepository = $this->replaceBucketOnBucketRepositoryMock($bucketMock);

        $this->assertSame([], $bucketRepository->executeQuery('someQuery', ['some' => 'parameters']));
    }

    public function testExecuteAQueryWithSingleValueResult()
    {
        $bucketMock = $this->createBucketMock(
            $this->returnCallback(function () {
                return 'foo';
            })
        );

        $bucketRepository = $this->replaceBucketOnBucketRepositoryMock($bucketMock);

        $this->assertSame(['foo'], $bucketRepository->executeQuery('someQuery', ['some' => 'parameters']));
    }

    public function testExecuteAQueryWithResultConsistingOfMultipleDocuments()
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

    public function testExecuteAQueryWithResultIncludingBucketName()
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

    public function testGetAnInstanceOfQueryBuilder()
    {
        $queryBuilder = $this->bucketRepository->createQueryBuilder();

        $this->assertInstanceOf(QueryBuilder::class, $queryBuilder);
    }

    public function testGetQueryResultInResultModel()
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

    public function testExecuteAQueryWithSingleNonArrayResult()
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

    public function testGetQueryResultWithoutResultModel()
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

    /**
     * Replace the bucket in the BucketRepository as CouchbaseMock can't handle N1ql queries.
     *
     * @param \PHPUnit\Framework\MockObject\MockObject $bucketMock
     *
     * @return \BowlOfSoup\CouchbaseAccessLayer\Repository\BucketRepository|\PHPUnit\Framework\MockObject\MockObject|null
     */
    private function replaceBucketOnBucketRepositoryMock(MockObject $bucketMock)
    {
        try {
            $bucketRepository = clone $this->bucketRepository;

            $reflectionClass = new \ReflectionClass($bucketRepository);
            $reflectionProperty = $reflectionClass->getProperty('bucket');
            $reflectionProperty->setAccessible(true);
            $reflectionProperty->setValue($bucketRepository, $bucketMock);

            return $bucketRepository;
        } catch (\ReflectionException $e) {
            trigger_error($e->getMessage(), E_USER_ERROR);

            return null;
        }
    }

    /**
     * @param \PHPUnit\Framework\MockObject\Stub\ReturnCallback $returnCallback
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function createBucketMock(ReturnCallback $returnCallback): MockObject
    {
        $bucketMock = $this->getMockBuilder(\stdClass::class)->setMethods(['query'])->getMock();
        $bucketMock
            ->expects($this->once())
            ->method('query')
            ->with(
                // Asserting that the input parameters for method 'query' are correct
                $this->callback(function ($query) {
                    Assert::assertInstanceOf(N1qlQuery::class, $query);

                    return $query;
                }),
                $this->callback(function ($jsonAsArray) {
                    Assert::assertTrue($jsonAsArray);

                    return $jsonAsArray;
                })
            )
            ->will($returnCallback);

        return $bucketMock;
    }
}
