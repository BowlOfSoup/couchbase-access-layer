[![Minimum PHP Version](https://img.shields.io/badge/php-%5E%207.0-blue.svg?no-cache=1)](https://php.net/)
[![Build Status](https://travis-ci.org/BowlOfSoup/couchbase-access-layer.svg?branch=master)](https://travis-ci.org/BowlOfSoup/couchbase-access-layer)
[![Coverage Status](https://coveralls.io/repos/github/BowlOfSoup/couchbase-access-layer/badge.svg?branch=master)](https://coveralls.io/github/BowlOfSoup/couchbase-access-layer?branch=master)

* [Installation](#installation)
* [Usage](#usage)
  - [Do use Parameters](#do-use-parameters)
  - [The query builder supports the following N1QL clauses](#the-query-builder-supports-the-following-n1ql-clauses)
  - [Examples](#examples)
  - [Using a sub query in a FROM statement](#using-a-sub-query-in-a-from-statement)
* [Unit tests](#unit-tests)

Installation
------------
    composer require bowlofsoup/couchbase-access-layer

Couchbase Access Layer
======================

A **simple** layer on top of the PHP Couchbase SDK. Basically you get a _bucket_ repository class which acts as a layer between your code and Couchbase.

The repository helps you to:
- Quickly setup a Couchbase connection.
- A handy to use BucketRepository to quickly query a **single** bucket.
- Create queries with a so called '**query builder**', this helps you build maintainable and easy to read N1ql queries.
- Processes the differences in result syntax you can get back from Couchbase into a consistent query result.
- See below for more examples!

Usage
-----

#### Do use Parameters

Important: When building a query, always try to use parameters.

Incorrect:

    $queryBuilder
        ->where('data.creationDate >= ' . $creationDate);

Correct:

    $queryBuilder
        ->where('data.creationDate >= $creationDate')
        ->setParameter('creationDate', '2019-01-10');

This will prevent injection.

#### The query builder supports the following N1QL clauses

- `SELECT` with optional `DISTINCT` and `RAW`
- `FROM` with optional alias, subquery also implemented (= mandatory alias)
- `USE` which means: `USE KEYS` and `USE INDEX`
- `WHERE`
- `GROUP BY`
- `ORDER BY`
- `LIMIT`
- `OFFSET`

Documentation for clauses can be found [On the Couchbase site](https://docs.couchbase.com/server/6.0/n1ql/n1ql-language-reference/selectintro.html).

#### Examples

    <?php

    declare(strict_types=1);

    namespace Some\Name\Space;

    use BowlOfSoup\CouchbaseAccessLayer\Exception\CouchbaseQueryException;
    use BowlOfSoup\CouchbaseAccessLayer\Factory\ClusterFactory;
    use BowlOfSoup\CouchbaseAccessLayer\Model\Result;
    use BowlOfSoup\CouchbaseAccessLayer\Repository\BucketRepository;

    class Foo
    {
        public function exampleUsingTheQueryBuilder()
        {
            $result = $this->querySomeBucket();

            foreach ($result as $item) {
                // each $item contains a Couchbase document which matched the query.
            }

            // $result implements the \JsonSerializableInterface.
            $jsonEncoded = json_encode($result);

            // These (can) only differ when query with limit/offset is done.
            $resultCount = $result->getCount();
            $resultTotalCount = $result->getTotalCount();

            // Return just the data you queried
            $justTheData = $result->get();

            // Get only one (or the first) result
            $singleResult = $result->getFirstResult();
        }

        public function exampleUsingDirectCalls()
        {
            $clusterFactory = new ClusterFactory('couchbaseHost', 'couchbaseUsername', 'couchbasePassword');
            $bucketRepository = new BucketRepository('someBucketName', $clusterFactory);

            $bucketRepository->upsert('some document id', 'the content of the document');

            $bucketRepository->remove('some document id);

            $documentContent = $bucketRepository->getByKey('some document id');
        }

        public function exampleExecutingManualQuery()
        {
            $clusterFactory = new ClusterFactory('couchbaseHost', 'couchbaseUsername', 'couchbasePassword');
            $bucketRepository = new BucketRepository('someBucketName', $clusterFactory);

            // Creating an index for a bucket
            $bucketRepository->executeQuery(
                CREATE INDEX i_foo_field_name
                ON `' . $bucketRepository->getBucketName() . '`(`field`, `data.name`)
                WHERE (`documentType` = "foo")
            );

            $result = $bucketRepository->executeQuery(
                'SELECT someField FROM `bucket name` WHERE someOtherField = $someOtherField',
                ['someOtherField' => 'some value']
            );

            // This will only return one result.
            $result = $bucketRepository->executeQueryWithOneResult();
        }

        /**
         * @return \BowlOfSoup\CouchbaseAccessLayer\Model\Result
         */
        private function querySomeBucket(): Result
        {
            $clusterFactory = new ClusterFactory('couchbaseHost', 'couchbaseUsername', 'couchbasePassword');
            $bucketRepository = new BucketRepository('someBucketName', $clusterFactory);

            $queryBuilder = $bucketRepository->createQueryBuilder();

            $queryBuilder
                ->select('data.name')
                // See that you can put in your own logic, like COUNT and such:
                ->select('COUNT(data.name) AS count')
                ->where('foo = $foo')
                ->where('someField = $someField')
                ->where('type = $type');

            $queryBuilder->setParameters([
                'foo' => 'someFooValue',
                'someKey' => 'someKeyValue',
                'type' => 'someDocumentType',
            ]);

            $queryBuilder
                ->where('data.creationDate >= $creationDate')
                ->setParameter('creationDate', '2019-01-10');

            $queryBuilder
                ->groupBy('data.name')
                ->limit(10)
                ->offset(20);

            try {
                return $bucketRepository->getResult($queryBuilder);
            } catch (CouchbaseQueryException $e) {
                // Something went wrong.
            }
        }
    }

For a `WHERE` clause you can put in logic yourself:

    $queryBuilder->where('CONTAINS(SUBSTR(name,0,1),"C")');

For a `GROUP BY` clause you can put in logic yourself too:

    $queryBuilder->groupBy('city LETTING MinimumThingsToSee = 400 HAVING COUNT(DISTINCT name) > MinimumThingsToSee');

#### Using a sub query in a FROM statement

When using a sub select in a `FROM` statement with `$queryBuilder->fromSubQuery()` and you're using parameters,
put the parameters in the 'master' query builder. More info on [this](https://docs.couchbase.com/server/6.0/n1ql/n1ql-language-reference/from.html) page.

    $queryBuilderForSubSelect = new QueryBuilder('bucket_name');
    $queryBuilderForSubSelect->where('type = $foo');

    $queryBuilder = new QueryBuilder('bucket_name');

    $queryBuilder
        ->select('q1.someFieldOfTheSubSelect')
        ->fromSubQuery($queryBuilderForSubSelect, 'q1')
        ->setParameter('foo', 'value1');

Unit tests
----------

When running the unit tests, a 'CouchbaseMock.jar' file (a dummy Couchbase instance, ~3.8 MB) will be downloaded.
