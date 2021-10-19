<?php

namespace Jiajushe\HyperfHelper\MongoDB;

use Hyperf\Task\Annotation\Task;
use MongoDB\Driver\Manager;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\WriteConcern;


class ModelTask
{
    protected Manager $manager;
    protected string $namespace;

    public function manager(array $config): Manager
    {
        if ($this->manager instanceof Manager) {
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
            'write_errors' => $res->getWriteErrors(),
            'write_concern_error' => $res->getWriteConcernError()
        ];
    }
}