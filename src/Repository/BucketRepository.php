<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseAccessLayer\Repository;

use BowlOfSoup\CouchbaseAccessLayer\Builder\QueryBuilder;
use BowlOfSoup\CouchbaseAccessLayer\Factory\ClusterFactory;
use BowlOfSoup\CouchbaseAccessLayer\Model\Result;
use Couchbase\Bucket;
use Couchbase\Exception\CouchbaseException;
use Couchbase\InsertOptions;
use Couchbase\MutationResult;
use Couchbase\QueryOptions;
use Couchbase\UpsertOptions;

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

    /** @var \Couchbase\Cluster  */
    protected $cluster;

    /**
     * @param string $bucketName
     * @param \BowlOfSoup\CouchbaseAccessLayer\Factory\ClusterFactory $clusterFactory
     * @param string $bucketPassword
     */
    public function __construct(
        string $bucketName,
        ClusterFactory $clusterFactory,
        string $bucketPassword = ''
    ) {
        $this->bucketName = $bucketName;

        $this->cluster = $clusterFactory->create();
        try {
            $this->bucket = $this->cluster->bucket($bucketName);
        } catch (\Throwable $t) {
            $this->bucket = $this->cluster->bucket($bucketName, $bucketPassword);
        }
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
            return json_decode(json_encode($this->bucket->defaultCollection()->get($key)->content()), true);
        } catch (CouchbaseException $e) {
            return null;
        }
    }

    /**
     * @param string|array $ids
     * @param mixed $value
     * @param array $options
     *
     * @return MutationResult|array
     */
    public function upsert($ids, $value, array $options = [])
    {
        $options = new UpsertOptions();

        return $this->bucket->defaultCollection()->upsert($ids, $value, $options);
    }

    public function insert($ids, $value)
    {
        $options = new InsertOptions();

        return $this->bucket->defaultCollection()->insert($ids, $value);
    }

    /**
     * @param string $id
     *
     * @throws CouchbaseException
     */
    public function remove(string $id): void
    {
        $this->bucket->defaultCollection()->remove($id);
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
        $options = new QueryOptions();
        if (count($params) > 0) {
            $options->namedParameters($params);
        }
        $result = $this->cluster->query(
            $query,
            $options)
            ->rows();

        return $this->cleanResult($this->extractQueryResult($result));
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

        $result = new Result($this->cleanResult($this->extractQueryResult($queryResult)));
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

    private function cleanResult(array $result): array
    {
        $returnableResult = [];

        foreach($result[0] as $item) {
            if (!is_array($item)) {
                var_dump($item);die();
            }
            $returnableResult[] = $item[$this->bucket->name()];
        }

        return $returnableResult;
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
     * @return array|null
     */
    public function getResultUnprocessed(QueryBuilder $queryBuilder)
    {
        $options = null;
        if (false === empty($queryBuilder->getParameters())) {
            $options = new QueryOptions();
            $options->namedParameters($queryBuilder->getParameters());
        }

        return $this->cluster->query(
                $queryBuilder->getQuery(),
                $options)
            ->rows();
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
