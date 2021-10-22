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

    public function collection(array $config): Collection
    {
        if (isset($this->collection)) {
            return $this->collection;
        }
        if (!$config['username']) {
            $uri = 'mongodb://' . $config['host'] . ':' . $config['port'];
        } else {
            $uri = 'mongodb://' . $config['username'] . ':' . $config['password'] . '@' . $config['host'] . ':' . $config['port'];
        }
        $this->client = new Client($uri);
        return $this->collection = $this->client->selectCollection($config['database'], $config['collection']);
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
            'confirm' => $res->isAcknowledged(),
            'error' => $res->getWriteConcernError(),
            'error_arr' => $res->getWriteErrors(),
            'inserted' => $res->getInsertedCount(),
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
            'confirm' => $res->isAcknowledged(),
            'error' => $res->getWriteConcernError(),
            'error_arr' => $res->getWriteErrors(),
            'matched' => $res->getMatchedCount(),
            'modified' => $res->getModifiedCount(),
        ];
    }

    /**
     * 删除
     * @Task(timeout=30)
     * @param array $config
     * @param array $filter
     * @param int $timeout
     * @return array
     */
    public function delete(array $config, array $filter, int $timeout = 1000): array
    {
        $bulkWrite = $this->bulkWrite();
        $bulkWrite->delete($filter);
        $res = $this->manager($config)->executeBulkWrite($this->namespace, $bulkWrite, ['writeConcern' => $this->writeConcern($timeout)]);
        return [
            'confirm' => $res->isAcknowledged(),
            'error' => $res->getWriteConcernError(),
            'error_arr' => $res->getWriteErrors(),
            'deleted' => $res->getDeletedCount(),
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

    /**
     * 获取数据条数
     * @Task (timeout=30)
     * @param array $config
     * @param array $filter
     * @param array $options
     * @return int
     */
    public function count(array $config, array $filter = [], array $options = []): int
    {
        return $this->collection($config)->countDocuments($filter, $options);
    }
}