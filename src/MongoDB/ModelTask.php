<?php

namespace Jiajushe\HyperfHelper\MongoDB;

use Hyperf\Task\Annotation\Task;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Driver\Exception\Exception;
use MongoDB\Driver\Manager;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Query;
use MongoDB\Driver\ReadPreference;
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
     * 插入
     * @Task(timeout=30)
     * @param array $config
     * @param array $document
     * @param int $timeout
     * @return array
     */
    public function insert(array $config, array $document, int $timeout = 1000): array
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
     * 更新
     * @Task(timeout=30)
     * @param array $config
     * @param array $filter
     * @param array $document
     * @param int $timeout
     * @return array
     */
    public function update(array $config, array $filter, array $document, int $timeout = 1000): array
    {
        $bulkWrite = $this->bulkWrite();
        $bulkWrite->update($filter, ['$set' => $document], ['multi' => true, 'upsert' => false]);
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
     * 查询
     * @Task(timeout=30)
     * @param array $config
     * @param array $filter
     * @param array $options
     * @return \Hyperf\Utils\Collection
     * @throws Exception
     */
    public function query(array $config, array $filter, array $options = []): \Hyperf\Utils\Collection
    {
        $query = new Query($filter, $options);
        $readPreference = new ReadPreference(ReadPreference::RP_PRIMARY);
        $res = $this->manager($config)->executeQuery($this->namespace, $query, $readPreference);
        $res = \Hyperf\Utils\Collection::make($res);
        if (!isset($options['projection']['_id']) || $options['projection']['_id']) {
            $res = $res->each(function ($item) {
                $item->id = (string)$item->_id;
                unset($item->_id);
            });
        }

        return $res;
    }
}