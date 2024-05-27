<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseAccessLayer\Repository;

use BowlOfSoup\CouchbaseAccessLayer\Builder\QueryBuilder;
use BowlOfSoup\CouchbaseAccessLayer\Factory\ClusterFactory;
use BowlOfSoup\CouchbaseAccessLayer\Model\Result;
use Couchbase\BucketInterface;
use Couchbase\ClusterInterface;
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
    protected string $bucketName;

    protected BucketInterface $bucket;

    protected ClusterInterface $cluster;

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

    public function getBucket(): BucketInterface
    {
        return $this->bucket;
    }

    public function getBucketName(): string
    {
        return $this->bucketName;
    }

    /**
     * Use this method to get a Couchbase document by its key.
     *
     * The get() method always returns a stdClass object, this recursively converts to assoc array.
     */
    public function getByKey(string $key): ?array
    {
        try {
            return json_decode(json_encode($this->bucket->defaultCollection()->get($key)->content()), true);
        } catch (CouchbaseException $e) {
            return null;
        }
    }

    /**
     * @param string $ids
     * @param mixed $value
     * @param array $options
     */
    public function upsert(string $id, $value, array $options = []): MutationResult
    {
        $options = new UpsertOptions();

        return $this->bucket->defaultCollection()->upsert($id, $value, $options);
    }

    /**
     * @param string $ids
     * @param $value
     * @return MutationResult
     */
    public function insert(string $id, $value): MutationResult
    {
        return $this->bucket->defaultCollection()->insert($id, $value);
    }

    /**
     * @throws CouchbaseException
     */
    public function remove(string $id): void
    {
        $this->bucket->defaultCollection()->remove($id);
    }

    public function executeQuery(string $query, array $params = []): ?array
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

    public function executeQueryWithOneResult(string $query, array $params = []): mixed
    {
        $result = $this->executeQuery($query, $params);

        return reset($result);
    }

    public function createQueryBuilder(): QueryBuilder
    {
        return new QueryBuilder($this->bucketName);
    }

    /**
     * @throws \BowlOfSoup\CouchbaseAccessLayer\Exception\CouchbaseQueryException
     */
    public function getResult(QueryBuilder $queryBuilder): Result
    {
        $queryResult = $this->getResultUnprocessed($queryBuilder);

        $result = new Result($this->cleanResult($this->extractQueryResult($queryResult)));

        $result
            ->setCount(count($result))
            ->setTotalCount(count($result));

        return $result;
    }

    /**
     * @throws \BowlOfSoup\CouchbaseAccessLayer\Exception\CouchbaseQueryException
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
            $returnableResult[] = $item[$this->bucket->name()];
        }

        return $returnableResult;
    }

    /**
     * @throws \BowlOfSoup\CouchbaseAccessLayer\Exception\CouchbaseQueryException
     */
    public function getResultUnprocessed(QueryBuilder $queryBuilder): ?array
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

    private function extractQueryResult(mixed $rawQueryResult): array
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
