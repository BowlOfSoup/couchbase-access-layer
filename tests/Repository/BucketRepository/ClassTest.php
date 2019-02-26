<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseAccessLayer\Test\Repository\BucketRepository;

use BowlOfSoup\CouchbaseAccessLayer\Builder\QueryBuilder;
use Couchbase\Bucket;
use Couchbase\Exception as CouchbaseException;

/**
 * Tests all other units from BucketRepository that where not tested in other test classes in this namespace.
 */
class ClassTest extends AbstractTest
{
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

    public function testGetDocumentByKeyButDocumentDoesNotExist()
    {
        $this->assertNull($this->bucketRepository->getByKey('123'));
    }


    public function testGetAnInstanceOfQueryBuilder()
    {
        $queryBuilder = $this->bucketRepository->createQueryBuilder();

        $this->assertInstanceOf(QueryBuilder::class, $queryBuilder);
    }

    public function testUpsertADocument()
    {
        $id = uniqid();
        $value = ['someData' => 'test123', 'foo' => ['somethingsomething']];

        $this->bucketRepository->upsert($id, $value);

        $this->assertSame($value, $this->bucketRepository->getByKey($id));
    }

    /**
     * @throws \Couchbase\Exception
     */
    public function testRemoveADocument()
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
    public function testRemoveADocumentWhichDoesNotExist()
    {
        $this->bucketRepository->remove('123');
    }

    /**
     * @throws \Couchbase\Exception
     *
     * @expectedException \Couchbase\Exception
     * @expectedExceptionMessage foo
     */
    public function testRemoveADocumentSomeExceptionThrown()
    {
        $bucketMock = $this->getMockBuilder(\stdClass::class)->setMethods(['remove'])->getMock();
        $bucketMock
            ->expects($this->once())
            ->method('remove')
            ->will($this->throwException(new CouchbaseException('foo')));

        $bucketRepository = $this->replaceBucketOnBucketRepositoryMock($bucketMock);

        $bucketRepository->remove('123');
    }
}
