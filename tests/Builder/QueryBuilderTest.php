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
        $queryBuilder = new QueryBuilder($this->getDefaultBucket());

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

        $this->assertSame(
            'SELECT someField FROM `default` WHERE someField = $someField AND anotherValue = $anotherValue AND type = $type AND data.foo > $dataFoo GROUP BY someField ORDER BY data.someOrderingField DESC LIMIT 10 OFFSET 5',
            $queryBuilder->getQuery()
        );
        $this->assertSame([
            'someField' => '12346782',
            'anotherValue' => 'ThisIsADocument',
            'type' => 'SomeDifferentTypeOfDocument',
            'dataFoo' => 'bar',
        ], $queryBuilder->getParameters());
    }
}
