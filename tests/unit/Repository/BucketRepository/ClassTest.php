<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseAccessLayer\Test\unit\Repository\BucketRepository;

use BowlOfSoup\CouchbaseAccessLayer\Builder\QueryBuilder;
use BowlOfSoup\CouchbaseAccessLayer\Test\AbstractTest;
use Couchbase\Bucket;
use Couchbase\Exception\CouchbaseException;
use Couchbase\Exception\DocumentNotFoundException;

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
}
