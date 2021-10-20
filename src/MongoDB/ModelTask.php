<?php

namespace Jiajushe\HyperfHelper\MongoDB;

use Hyperf\Task\Annotation\Task;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Driver\Manager;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\WriteConcern;


class ModelTask
{
    protected Manager $manager;
    protected Client $client;
    protected string $namespace;
    protected Collection $collection;

    public function manager(array $config): Manager
    {
        if (isset($this->manager)) {
            return $this->manager;
        }
        if (!$config['username']) {
            $uri = 'mongodb://' . $config['host'] . ':' . $config['port'];
        } else {
            $uri = 'mongodb://' . $config['username'] . ':' . $config['password'] . '@' . $config['host'] . ':' . $config['port'];
        }
        $this->namespace = $config['database'] . '.' . $config['collection'];
        return $this->manager = new Manager($uri);
    }

    public function collection(array $config)
    {
        if (isset($this->client)) {
            return $this->client;
        }
        if (!$config['username']) {
            $uri = 'mongodb://' . $config['host'] . ':' . $config['port'];
        } else {
            $uri = 'mongodb://' . $config['username'] . ':' . $config['password'] . '@' . $config['host'] . ':' . $config['port'];
        }
        $this->namespace = $config['database'] . '.' . $config['collection'];
        $client = new Client($uri);
        return $this->collection = $client->selectCollection($config['database'],$config['collection']);
    }

    /**
     * @Task(timeout=30)
     * @param array $config
     * @param array $document
     * @return int[]
     */
    public function create(array $config,array $document): array
    {
        $res = $this->collection($config)->insertOne($document);
        pp($res);
        return ['res'=>1];
    }


    /**
     * @return BulkWrite
     */
    final protected function bulkWrite(): BulkWrite
    {
        return new BulkWrite();
    }

    /**
     * @param int $timeout
     * @return WriteConcern
     */
    final protected function writeConcern(int $timeout = 1000): WriteConcern
    {
        return new WriteConcern(WriteConcern::MAJORITY, $timeout);
    }

    /**
     * @Task(timeout=30)
     * @param array $config
     * @param array $document
     * @param int $timeout
     * @return array
     */
    public function insert(array $config,array $document, int $timeout = 1000): array
    {
        $bulkWrite = $this->bulkWrite();
        foreach ($document as $row) {
            $bulkWrite->insert($row);
        }
        $res = $this->manager($config)->executeBulkWrite($this->namespace, $bulkWrite, ['writeConcern' => $this->writeConcern($timeout)]);
        return [
            'inserted_count' => $res->getInsertedCount(),
            'upserted_count' => $res->getUpsertedCount(),
            'upserted_ids' => $res->getUpsertedIds(),
            'matched_count' => $res->getMatchedCount(),
            'modified_count' => $res->getModifiedCount(),
            'deleted_count' => $res->getDeletedCount(),
            'write_concern_error' => $res->getWriteConcernError(),
            'write_errors' => $res->getWriteErrors(),
            'is_acknowledged' => $res->isAcknowledged(),
        ];
    }

    /**
     * @param array $config
     * @param array $document
     * @param int $timeout
     * @return array
     */
    public function insertNoTask(array $config,array $document, int $timeout = 1000): array
    {
        $bulkWrite = $this->bulkWrite();
        foreach ($document as $row) {
            $bulkWrite->insert($row);
        }
        $res = $this->manager($config)->executeBulkWrite($this->namespace, $bulkWrite, ['writeConcern' => $this->writeConcern($timeout)]);
        return [
            'inserted_count' => $res->getInsertedCount(),
            'upserted_count' => $res->getUpsertedCount(),
            'upserted_ids' => $res->getUpsertedIds(),
            'matched_count' => $res->getMatchedCount(),
            'modified_count' => $res->getModifiedCount(),
            'deleted_count' => $res->getDeletedCount(),
            'write_concern_error' => $res->getWriteConcernError(),
            'write_errors' => $res->getWriteErrors(),
            'is_acknowledged' => $res->isAcknowledged(),
        ];
    }
}