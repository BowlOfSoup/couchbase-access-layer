<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseAccessLayer\Test\unit\Builder;

use BowlOfSoup\CouchbaseAccessLayer\Builder\QueryBuilder;
use BowlOfSoup\CouchbaseAccessLayer\Exception\CouchbaseQueryException;
use BowlOfSoup\CouchbaseAccessLayer\Model\Query;
use BowlOfSoup\CouchbaseAccessLayer\Test\unit\CouchbaseMock\CouchbaseTestCase;

class QueryBuilderTest extends CouchbaseTestCase
{
    /**
     * @throws \BowlOfSoup\CouchbaseAccessLayer\Exception\CouchbaseQueryException
     */
    public function testGetQuery(): void
    {
        $queryBuilder = new QueryBuilder('default_bucket');

        $queryBuilder
            ->where('someField = $someField')
            ->where('anotherValue = $anotherValue')
            ->where('type = $type');

        $queryBuilder->setParameters([
            'someField' => '12346782',
            'anotherValue' => 'ThisIsADocument',
            'type' => 'SomeDifferentTypeOfDocument',
        ]);

        $queryBuilder
            ->where('data.foo > $dataFoo')
            ->setParameter('dataFoo', 'bar');

        $queryBuilder
            ->groupBy('someField')
            ->orderBy('data.someOrderingField', Query::ORDER_DESC)
            ->limit(10)
            ->offset(5);

        $queryBuilder->select('someField');
        $queryBuilder->selectMultiple(['foo', 'COUNT(bar) AS bar_counted']);

        $this->assertSame(
            'SELECT someField, foo, COUNT(bar) AS bar_counted FROM `default_bucket` WHERE someField = $someField AND anotherValue = $anotherValue AND type = $type AND data.foo > $dataFoo GROUP BY someField ORDER BY data.someOrderingField DESC LIMIT 10 OFFSET 5',
            $queryBuilder->getQuery()
        );
        $this->assertSame([
            'someField' => '12346782',
            'anotherValue' => 'ThisIsADocument',
            'type' => 'SomeDifferentTypeOfDocument',
            'dataFoo' => 'bar',
        ], $queryBuilder->getParameters());
    }

    /**
     * @throws \BowlOfSoup\CouchbaseAccessLayer\Exception\CouchbaseQueryException
     */
    public function testGetQueryWithWhereOr(): void
    {
        $queryBuilder = new QueryBuilder('default_bucket');

        $queryBuilder
            ->where('someField = $someField')
            ->whereOr('`key` IN [\'foo\', \'bar\']');

        $queryBuilder->setParameters([
            'someField' => '12346782',
            'anotherValue' => 'ThisIsADocument',
            'type' => 'SomeDifferentTypeOfDocument',
        ]);

        $queryBuilder
            ->where('data.foo > $dataFoo')
            ->setParameter('dataFoo', 'bar');

        $queryBuilder
            ->groupBy('someField')
            ->orderBy('data.someOrderingField', Query::ORDER_DESC)
            ->limit(10)
            ->offset(5);

        $queryBuilder->select('someField');
        $queryBuilder->selectMultiple(['foo', 'COUNT(bar) AS bar_counted']);

        $this->assertSame(
            'SELECT someField, foo, COUNT(bar) AS bar_counted FROM `default_bucket` WHERE someField = $someField AND data.foo > $dataFoo OR `key` IN [\'foo\', \'bar\'] GROUP BY someField ORDER BY data.someOrderingField DESC LIMIT 10 OFFSET 5',
            $queryBuilder->getQuery()
        );
        $this->assertSame([
            'someField' => '12346782',
            'anotherValue' => 'ThisIsADocument',
            'type' => 'SomeDifferentTypeOfDocument',
            'dataFoo' => 'bar',
        ], $queryBuilder->getParameters());
    }

    /**
     * @throws \BowlOfSoup\CouchbaseAccessLayer\Exception\CouchbaseQueryException
     */
    public function testGetQuerySelectAll(): void
    {
        $queryBuilder = new QueryBuilder('default_bucket');

        $this->assertSame(
            'SELECT * FROM `default_bucket`',
            $queryBuilder->getQuery()
        );
    }

    /**
     * @throws \BowlOfSoup\CouchbaseAccessLayer\Exception\CouchbaseQueryException
     */
    public function testQueryWithRaw(): void
    {
        $queryBuilder = new QueryBuilder('default_bucket');

        $queryBuilder
            ->select('data.someField')
            ->selectRaw();

        $this->assertSame(
            'SELECT RAW data.someField FROM `default_bucket`',
            $queryBuilder->getQuery()
        );
    }

    /**
     * @throws \BowlOfSoup\CouchbaseAccessLayer\Exception\CouchbaseQueryException
     */
    public function testQueryWithDistinctAndRawUsingAnIndex(): void
    {
        $queryBuilder = new QueryBuilder('default_bucket');

        $queryBuilder
            ->from('bucketName') // You can query all buckets you have permission for.
            ->select('data.someField', Query::DISTINCT)
            ->selectRaw()
            ->useIndex('some_index_name');

        $this->assertSame(
            'SELECT DISTINCT RAW data.someField FROM `bucketName` USE INDEX (some_index_name USING GSI)',
            $queryBuilder->getQuery()
        );
    }

    /**
     * @throws \BowlOfSoup\CouchbaseAccessLayer\Exception\CouchbaseQueryException
     */
    public function testQueryWithSubSelectInFrom(): void
    {
        $queryBuilderForSubSelect = new QueryBuilder('possibly_some_other_bucket');
        $queryBuilderForSubSelect->where('type = $foo');

        $queryBuilder = new QueryBuilder('default_bucket');

        $queryBuilder
            ->select('q1.someFieldOfTheSubSelect')
            ->fromSubQuery($queryBuilderForSubSelect, 'q1')
            ->setParameter('foo', 'value1');

        $queryBuilder->orderBy('q1.someFieldOfTheSubSelect');

        $this->assertSame(
            'SELECT q1.someFieldOfTheSubSelect FROM (SELECT * FROM `possibly_some_other_bucket` WHERE type = $foo) q1  ORDER BY q1.someFieldOfTheSubSelect ASC',
            $queryBuilder->getQuery()
        );
    }

    /**
     * @throws \BowlOfSoup\CouchbaseAccessLayer\Exception\CouchbaseQueryException
     */
    public function testQueryWithUseOfKeys(): void
    {
        $queryBuilder = new QueryBuilder('default_bucket');

        $queryBuilder
            ->useKey('document_id_1')
            ->useKey('some_other_document_key');

        $this->assertSame(
            'SELECT * FROM `default_bucket` USE KEYS ["document_id_1", "some_other_document_key"]',
            $queryBuilder->getQuery()
        );
    }

    /**
     * @throws \BowlOfSoup\CouchbaseAccessLayer\Exception\CouchbaseQueryException
     */
    public function testQueryWithUseOfKeysMultipleGiven(): void
    {
        $queryBuilder = new QueryBuilder('default_bucket');

        $queryBuilder->useKeys(['document_id_1', 'some_other_document_key']);

        $this->assertSame(
            'SELECT * FROM `default_bucket` USE KEYS ["document_id_1", "some_other_document_key"]',
            $queryBuilder->getQuery()
        );
    }

    public function testQueryHasExceptionBecauseNoFromWasGiven(): void
    {
        $this->expectException(CouchbaseQueryException::class);
        $this->expectExceptionMessage("Can't build N1QL query because of missing 'FROM'.");

        $queryBuilder = new QueryBuilder();
        $queryBuilder->getQuery();
    }

    public function testQueryHasExceptionBecauseMultipleSelectsAreUsedWithRawClause(): void
    {
        $this->expectException(CouchbaseQueryException::class);
        $this->expectExceptionMessage("Can only use 'SELECT RAW' when exactly one property is selected.");

        $queryBuilder = new QueryBuilder('default_bucket');

        $queryBuilder
            ->select('data.someField')
            ->select('data.someOtherField')
            ->selectRaw();

        $queryBuilder->getQuery();
    }

    public function testQueryHasExceptionBecauseNoSelectsAreUsedWithRawClause(): void
    {
        $this->expectException(CouchbaseQueryException::class);
        $this->expectExceptionMessage("Can only use 'SELECT RAW' when exactly one property is selected.");

        $queryBuilder = new QueryBuilder('default_bucket');
        $queryBuilder->selectRaw();
        $queryBuilder->getQuery();
    }
}
