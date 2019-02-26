<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseAccessLayer\Repository;

use BowlOfSoup\CouchbaseAccessLayer\Builder\QueryBuilder;
use BowlOfSoup\CouchbaseAccessLayer\Factory\ClusterFactory;
use BowlOfSoup\CouchbaseAccessLayer\Model\Result;
use Couchbase\Bucket;
use Couchbase\Exception as CouchbaseException;
use Couchbase\N1qlQuery;

/**
 * Repository to get data out of a single Couchbase bucket.
 */
class BucketRepository
{
    const RESULT_AS_ARRAY = true;

    /** @var string */
    protected $bucketName;

    /** @var \Couchbase\Bucket */
    protected $bucket;

    /**
     * @param string $bucketName
     * @param \BowlOfSoup\CouchbaseAccessLayer\Factory\ClusterFactory $clusterFactory
     */
    public function __construct(
        string $bucketName,
        ClusterFactory $clusterFactory
    ) {
        $this->bucketName = $bucketName;

        $cluster = $clusterFactory->create();
        $this->bucket = $cluster->openBucket($bucketName, '');
    }

    /**
     * @return \Couchbase\Bucket
     */
    public function getBucket(): Bucket
    {
        return $this->bucket;
    }

    /**
     * @return string
     */
    public function getBucketName(): string
    {
        return $this->bucketName;
    }

    /**
     * Use this method to get a Couchbase document by its key.
     *
     * The get() method always returns a stdClass object, this recursively converts to assoc array.
     *
     * @param string $key
     *
     * @return array|null
     */
    public function getByKey($key)
    {
        try {
            return json_decode(json_encode($this->bucket->get($key)->value), true);
        } catch (CouchbaseException $e) {
            return null;
        }
    }

    /**
     * @param string|array $ids
     * @param mixed $value
     * @param array $options
     *
     * @return \Couchbase\Document|array
     */
    public function upsert($ids, $value, array $options = [])
    {
        return $this->bucket->upsert($ids, $value, $options);
    }

    /**
     * @param string $id
     *
     * @throws \Couchbase\Exception
     */
    public function remove(string $id)
    {
        try {
            $this->bucket->remove($id);
        } catch(CouchbaseException $exception) {
            if ($exception->getCode() === COUCHBASE_KEY_ENOENT) {
                // Document does not exist; No need to throw error.
            } else {
                throw $exception;
            }
        }
    }

    /**
     * Input a query string and query parameters.
     *
     * @param string $query
     * @param array $params
     *
     * @return array|null
     */
    public function executeQuery(string $query, array $params = [])
    {
        $query = N1qlQuery::fromString($query);
        $query->namedParams($params);

        $result = $this->bucket->query($query, static::RESULT_AS_ARRAY);

        return $this->extractQueryResult($result);
    }

    /**
     * Input a query string and query parameters, get single, or first result.
     *
     * @param string $query
     * @param array $params
     *
     * @return mixed
     */
    public function executeQueryWithOneResult(string $query, array $params = [])
    {
        $result = $this->executeQuery($query, $params);

        return reset($result);
    }

    /**
     * @return \BowlOfSoup\CouchbaseAccessLayer\Builder\QueryBuilder
     */
    public function createQueryBuilder(): QueryBuilder
    {
        return new QueryBuilder($this->bucketName);
    }

    /**
     * Get result for a Query Builder, transforms it into a result object.
     *
     * @param \BowlOfSoup\CouchbaseAccessLayer\Builder\QueryBuilder $queryBuilder
     *
     * @throws \BowlOfSoup\CouchbaseAccessLayer\Exception\CouchbaseQueryException
     *
     * @return \BowlOfSoup\CouchbaseAccessLayer\Model\Result
     */
    public function getResult(QueryBuilder $queryBuilder): Result
    {
        $queryResult = $this->getResultUnprocessed($queryBuilder);

        $result = new Result($this->extractQueryResult($queryResult));
        if (isset($queryResult->metrics)) {
            // metrics key does not exist when no limit of offset are given in a N1ql query.
            $result
                ->setCount((int) $queryResult->metrics['resultCount'])
                ->setTotalCount(isset($queryResult->metrics['sortCount']) ? (int) $queryResult->metrics['sortCount'] : (int) $queryResult->metrics['resultCount']);
        } else {
            $result
                ->setCount(count($result))
                ->setTotalCount(count($result));
        }

        return $result;
    }

    /**
     * Get result for a Query Builder, returns a consistent result.
     *
     * This method strips out the bucket name from the result set.
     *
     * @param \BowlOfSoup\CouchbaseAccessLayer\Builder\QueryBuilder $queryBuilder
     *
     * @throws \BowlOfSoup\CouchbaseAccessLayer\Exception\CouchbaseQueryException
     *
     * @return array
     */
    public function getResultAsArray(QueryBuilder $queryBuilder): array
    {
        $result = $this->getResult($queryBuilder);

        return $result->get();
    }

    /**
     * Returns actual result of a query unprocessed and inconsistent.
     *
     * Pass this Query Builder to BucketRepository->getResult() to get a consistent result.
     *
     * @param \BowlOfSoup\CouchbaseAccessLayer\Builder\QueryBuilder $queryBuilder
     *
     * @throws \BowlOfSoup\CouchbaseAccessLayer\Exception\CouchbaseQueryException
     *
     * @return object|array
     */
    public function getResultUnprocessed(QueryBuilder $queryBuilder)
    {
        $queryString = $queryBuilder->getQuery();

        $query = N1qlQuery::fromString($queryString);
        $query->namedParams($queryBuilder->getParameters());

        return $this->bucket->query($query, static::RESULT_AS_ARRAY);
    }

    /**
     * @param mixed $rawQueryResult
     *
     * This strips out the bucket name from the result set and makes it consistent.
     *
     * @return array
     */
    private function extractQueryResult($rawQueryResult): array
    {
        if (null === $rawQueryResult) {
            return [];
        }

        if (!isset($rawQueryResult->rows)) {
            return [$rawQueryResult];
        }

        if (!is_array($rawQueryResult->rows)) {
            return [$rawQueryResult->rows];
        }

        return array_map(
            function ($row) {
                if (1 === count($row)) {
                    return reset($row);
                }

                if (array_key_exists($this->bucketName, $row)) {
                    return $row[$this->bucketName];
                }

                return $row;
            },
            $rawQueryResult->rows
        );
    }
}
