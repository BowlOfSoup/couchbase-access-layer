<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseAccessLayer\Test\Repository\BucketRepository;

use BowlOfSoup\CouchbaseAccessLayer\Factory\ClusterFactory;
use BowlOfSoup\CouchbaseAccessLayer\Repository\BucketRepository;
use Couchbase\Cluster;
use Couchbase\QueryResult;
use PHPUnit\Framework\MockObject\MockObject;

class ExecuteQueryTestBaseClass extends AbstractTestBaseClass
{
    public function testExecuteAQueryWithNoResult(): void
    {
        $clusterMock = $this
            ->getMockBuilder(Cluster::class)
            ->onlyMethods(['bucket', 'query'])
            ->disableOriginalConstructor()
            ->getMock();
        $clusterMock
            ->expects($this->once())
            ->method('bucket')
            ->with('default')
            ->willReturn($this->bucket);
        $queryResult = $this
            ->getMockBuilder(QueryResult::class)
            ->onlyMethods(['rows'])
            ->disableOriginalConstructor()
            ->getMock();
        $queryResult->method('rows')->willReturn(null);
        $clusterMock
            ->expects($this->once())
            ->method('query')
            ->willReturn($queryResult);

        /** @var MockObject|ClusterFactory $clusterFactoryMock */
        $clusterFactoryMock = $this
            ->getMockBuilder(ClusterFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $clusterFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($clusterMock);

        $bucketRepository = new BucketRepository('default', $clusterFactoryMock);

        $this->assertSame([], $bucketRepository->executeQuery('someQuery', ['some' => 'parameters']));
    }

    public function testExecuteAQueryWithASingleValueAndNoRowSyntaxInTheQueryResult(): void
    {
        $bucketMock = $this->createBucketMock(
            function () {
                return 'foo';
            }
        );

        $bucketRepository = $this->replaceBucketOnBucketRepositoryMock($bucketMock);

        $this->assertSame(['foo'], $bucketRepository->executeQuery('someQuery', ['some' => 'parameters']));
    }

    public function testExecuteAQueryWithASingleValue(): void
    {
        $clusterMock = $this
            ->getMockBuilder(Cluster::class)
            ->onlyMethods(['bucket', 'query'])
            ->disableOriginalConstructor()
            ->getMock();
        $clusterMock
            ->expects($this->once())
            ->method('bucket')
            ->with('default')
            ->willReturn($this->bucket);
        $queryResult = $this
            ->getMockBuilder(QueryResult::class)
            ->onlyMethods(['rows'])
            ->disableOriginalConstructor()
            ->getMock();
        $queryResult->method('rows')->willReturn(
            function () {
                $result = new \stdClass;
                $result->rows = 'foo';

                return $result;
            }
        );
        $clusterMock
            ->expects($this->once())
            ->method('query')
            ->willReturn($queryResult);

        /** @var MockObject|ClusterFactory $clusterFactoryMock */
        $clusterFactoryMock = $this
            ->getMockBuilder(ClusterFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $clusterFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($clusterMock);

        $bucketRepository = new BucketRepository('default', $clusterFactoryMock);

        $this->assertSame(['foo'], $bucketRepository->executeQuery('someQuery', ['some' => 'parameters']));
    }

    public function testExecuteAQueryWithResultConsistingOfMultipleDocuments(): void
    {
        $clusterMock = $this
            ->getMockBuilder(Cluster::class)
            ->onlyMethods(['bucket', 'query'])
            ->disableOriginalConstructor()
            ->getMock();
        $clusterMock
            ->expects($this->once())
            ->method('bucket')
            ->with('default')
            ->willReturn($this->bucket);
        $queryResult = $this
            ->getMockBuilder(QueryResult::class)
            ->onlyMethods(['rows'])
            ->disableOriginalConstructor()
            ->getMock();
        $queryResult->method('rows')->willReturn(
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
            ]
        );
        $clusterMock
            ->expects($this->once())
            ->method('query')
            ->willReturn($queryResult);

        /** @var MockObject|ClusterFactory $clusterFactoryMock */
        $clusterFactoryMock = $this
            ->getMockBuilder(ClusterFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $clusterFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($clusterMock);

        $bucketRepository = new BucketRepository('default', $clusterFactoryMock);

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
        $clusterMock = $this
            ->getMockBuilder(Cluster::class)
            ->onlyMethods(['bucket', 'query'])
            ->disableOriginalConstructor()
            ->getMock();
        $clusterMock
            ->expects($this->once())
            ->method('bucket')
            ->with('default')
            ->willReturn($this->bucket);
        $queryResult = $this
            ->getMockBuilder(QueryResult::class)
            ->onlyMethods(['rows'])
            ->disableOriginalConstructor()
            ->getMock();
        $queryResult->method('rows')->willReturn(
            [ // document 1
                'someOtherBucketName' => [
                    'foo' => 'bar',
                ],
                'default' => [
                    'hello' => 'world',
                ]
        ]);
        $clusterMock
            ->expects($this->once())
            ->method('query')
            ->willReturn($queryResult);

        /** @var MockObject|ClusterFactory $clusterFactoryMock */
        $clusterFactoryMock = $this
            ->getMockBuilder(ClusterFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $clusterFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($clusterMock);

        $bucketRepository = new BucketRepository('default', $clusterFactoryMock);

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
        $clusterMock = $this
            ->getMockBuilder(Cluster::class)
            ->onlyMethods(['bucket', 'query'])
            ->disableOriginalConstructor()
            ->getMock();
        $clusterMock
            ->expects($this->once())
            ->method('bucket')
            ->with('default')
            ->willReturn($this->bucket);
        $queryResult = $this
            ->getMockBuilder(QueryResult::class)
            ->onlyMethods(['rows'])
            ->disableOriginalConstructor()
            ->getMock();
        $queryResult->method('rows')->willReturn([
            ['foo'],
            ['bar'],
        ]);
        $clusterMock
            ->expects($this->once())
            ->method('query')
            ->willReturn($queryResult);

        /** @var MockObject|ClusterFactory $clusterFactoryMock */
        $clusterFactoryMock = $this
            ->getMockBuilder(ClusterFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $clusterFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($clusterMock);

        $bucketRepository = new BucketRepository('default', $clusterFactoryMock);

        $this->assertSame('foo', $bucketRepository->executeQueryWithOneResult('someQuery', ['some' => 'parameters']));
    }
}
