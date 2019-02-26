<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseAccessLayer\Test\Builder;

use BowlOfSoup\CouchbaseAccessLayer\Builder\QueryBuilder;
use BowlOfSoup\CouchbaseAccessLayer\Model\Query;
use BowlOfSoup\CouchbaseAccessLayer\Test\CouchbaseMock\CouchbaseTestCase;

class QueryBuilderTest extends CouchbaseTestCase
{
    /**
     * @throws \BowlOfSoup\CouchbaseAccessLayer\Exception\CouchbaseQueryException
     */
    public function testGetQuery()
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
    public function testGetQuerySelectAll()
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
    public function testQueryWithRaw()
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
    public function testQueryWithDistinctAndRawUsingAnIndex()
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
    public function testQueryWithSubSelectInFrom()
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
    public function testQueryWithUseOfKeys()
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
    public function testQueryWithUseOfKeysMultipleGiven()
    {
        $queryBuilder = new QueryBuilder('default_bucket');

        $queryBuilder->useKeys(['document_id_1', 'some_other_document_key']);

        $this->assertSame(
            'SELECT * FROM `default_bucket` USE KEYS ["document_id_1", "some_other_document_key"]',
            $queryBuilder->getQuery()
        );
    }

    /**
     * @expectedException \BowlOfSoup\CouchbaseAccessLayer\Exception\CouchbaseQueryException
     * @expectedExceptionMessage Can't build N1QL query because of missing 'FROM'.
     */
    public function testQueryHasExceptionBecauseNoFromWasGiven()
    {
        $queryBuilder = new QueryBuilder();
        $queryBuilder->getQuery();
    }

    /**
     * @expectedException \BowlOfSoup\CouchbaseAccessLayer\Exception\CouchbaseQueryException
     * @expectedExceptionMessage Can only use 'SELECT RAW' when exactly one property is selected.
     */
    public function testQueryHasExceptionBecauseMultipleSelectsAreUsedWithRawClause()
    {
        $queryBuilder = new QueryBuilder('default_bucket');

        $queryBuilder
            ->select('data.someField')
            ->select('data.someOtherField')
            ->selectRaw();

        $queryBuilder->getQuery();
    }

    /**
     * @expectedException \BowlOfSoup\CouchbaseAccessLayer\Exception\CouchbaseQueryException
     * @expectedExceptionMessage Can only use 'SELECT RAW' when exactly one property is selected.
     */
    public function testQueryHasExceptionBecauseNoSelectsAreUsedWithRawClause()
    {
        $queryBuilder = new QueryBuilder('default_bucket');
        $queryBuilder->selectRaw();
        $queryBuilder->getQuery();
    }
}
