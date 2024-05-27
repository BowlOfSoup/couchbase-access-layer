<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseAccessLayer\Test\unit\Repository\BucketRepository;

use BowlOfSoup\CouchbaseAccessLayer\Builder\QueryBuilder;
use BowlOfSoup\CouchbaseAccessLayer\Factory\ClusterFactory;
use BowlOfSoup\CouchbaseAccessLayer\Repository\BucketRepository;
use BowlOfSoup\CouchbaseAccessLayer\Test\AbstractTest;
use Couchbase\Bucket;
use Couchbase\BucketInterface;
use Couchbase\ClusterInterface;
use Couchbase\Exception\CouchbaseException;
use Couchbase\Exception\DocumentNotFoundException;
use Couchbase\QueryOptions;
use Couchbase\QueryResult;

/**
 * Tests all other units from BucketRepository that where not tested in other test classes in this namespace.
 */
class ClassTest extends AbstractTest
{
    public function testGetBucket(): void
    {
        $this->assertInstanceOf(Bucket::class, $this->bucketRepository->getBucket());
    }

    public function testGetBucketName(): void
    {
        $this->assertSame('default', $this->bucketRepository->getBucketName());
    }

    public function testUpsertADocumentAndGetDocumentByKey(): void
    {
        $id = uniqid();
        $value = ['someData' => 'test123', 'foo' => ['somethingsomething']];

        $this->bucketRepository->upsert($id, $value);

        $this->assertSame($value, $this->bucketRepository->getByKey($id));

        $this->bucketRepository->remove($id);
    }

    public function testGetDocumentByKeyButDocumentDoesNotExist(): void
    {
        $this->assertNull($this->bucketRepository->getByKey('123'));
    }


    public function testGetAnInstanceOfQueryBuilder(): void
    {
        $queryBuilder = $this->bucketRepository->createQueryBuilder();

        $this->assertInstanceOf(QueryBuilder::class, $queryBuilder);
    }

    /**
     * @throws CouchbaseException
     */
    public function testRemoveADocument(): void
    {
        $id = uniqid();
        $value = ['someData' => 'test123', 'foo' => ['somethingsomething']];

        $this->bucketRepository->upsert($id, $value);

        $this->bucketRepository->remove($id);

        $this->assertNull($this->bucketRepository->getByKey($id));
    }

    public function testRemoveADocumentWhichDoesNotExist(): void
    {
        $this->expectException(DocumentNotFoundException::class);

        $this->bucketRepository->remove('123abc');
    }

    public function testConstructorHandlesSituationWherePasswordIsNeeded(): void
    {
        $clusterFactory = $this->createMock(ClusterFactory::class);
        $cluster = $this->createMock(ClusterInterface::class);
        $bucket = $this->createMock(Bucket::class);

        $cluster->expects($this->exactly(2))->method('bucket')->willReturnCallback(function($bucketName, $password = '') use ($bucket) {
            if ($password === '') {
                throw new \Exception('Test');
            }

            return $bucket;
        });

        $clusterFactory->expects($this->once())->method('create')->willReturn($cluster);

        $bucketRepository = new BucketRepository('default', $clusterFactory, 'password');

        self::assertInstanceOf(BucketInterface::class, $bucketRepository->getBucket());
    }

    public function testGetResultUnprocessedWithParametersPassesParametersCorrectly(): void
    {
        $clusterFactory = $this->createMock(ClusterFactory::class);
        $cluster = $this->createMock(ClusterInterface::class);
        $bucket = $this->createMock(Bucket::class);

        $cluster->expects($this->once())->method('bucket')->with('default')->willReturn($bucket);

        $clusterFactory->expects($this->once())->method('create')->willReturn($cluster);

        $cluster->expects($this->once())->method('query')->willReturnCallback(function($query, QueryOptions $options) {
            $export = QueryOptions::export($options);
            self::assertArrayHasKey('parameter', $export['namedParameters']);

            $resultMock = $this->createMock(QueryResult::class);
            $resultMock->expects($this->once())->method('rows')->willReturn([['id' => 1337]]);

            return $resultMock;
        });

        $bucketRepository = new BucketRepository('default', $clusterFactory);

        $queryBuilder = new QueryBuilder();
        $queryBuilder->from('default');
        $queryBuilder->setParameters(['parameter' => 'value']);

        $result = $bucketRepository->getResultUnprocessed($queryBuilder);

        self::assertIsArray($result);
        self::assertArrayHasKey('id', $result[0]);
        self::assertSame(1337, $result[0]['id']);
    }
}
