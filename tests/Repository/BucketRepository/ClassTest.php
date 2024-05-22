<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseAccessLayer\Test\Repository\BucketRepository;

use BowlOfSoup\CouchbaseAccessLayer\Builder\QueryBuilder;
use Couchbase\Bucket;
use Couchbase\Exception as CouchbaseException;

/**
 * Tests all other units from BucketRepository that where not tested in other test classes in this namespace.
 */
class ClassTestBaseClass extends AbstractTestBaseClass
{
    public function testGetBucket(): void
    {
        $this->assertInstanceOf(Bucket::class, $this->bucketRepository->getBucket());
    }

    public function testGetBucketName(): void
    {
        $this->assertSame('default', $this->bucketRepository->getBucketName());
    }

    public function testGetDocumentByKey(): void
    {
        $id = uniqid();
        $value = ['someData' => 'test123', 'foo' => ['somethingsomething']];

        $this->bucket->upsert($id, $value);

        $this->assertSame($value, $this->bucketRepository->getByKey($id));
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

    public function testUpsertADocument(): void
    {
        $id = uniqid();
        $value = ['someData' => 'test123', 'foo' => ['somethingsomething']];

        $this->bucketRepository->upsert($id, $value);

        $this->assertSame($value, $this->bucketRepository->getByKey($id));
    }

    /**
     * @throws \Couchbase\Exception
     */
    public function testRemoveADocument(): void
    {
        $id = uniqid();
        $value = ['someData' => 'test123', 'foo' => ['somethingsomething']];

        $this->bucketRepository->upsert($id, $value);

        $this->bucketRepository->remove($id);

        $this->assertNull($this->bucketRepository->getByKey($id));
    }

    /**
     * @throws \Couchbase\Exception
     */
    public function testRemoveADocumentWhichDoesNotExist(): void
    {
        $this->bucketRepository->remove('123');
    }

    /**
     * @throws \Couchbase\Exception
     */
    public function testRemoveADocumentSomeExceptionThrown(): void
    {
        $this->expectException(\Couchbase\Exception::class);
        $this->$this->expectExceptionMessage('foo');

        $bucketMock = $this->getMockBuilder(\stdClass::class)->onlyMethods(['remove'])->getMock();
        $bucketMock
            ->expects($this->once())
            ->method('remove')
            ->will($this->throwException(new CouchbaseException('foo')));

        $bucketRepository = $this->replaceBucketOnBucketRepositoryMock($bucketMock);

        $bucketRepository->remove('123');
    }
}
