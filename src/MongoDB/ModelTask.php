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
    public function manager(array $config): Manager
    {
        return new Manager($this->getUri($config));
    }

    public function namespace(array $config): string
    {
        return $config['database'] . '.' . $config['collection'];
    }

    public function client(array $config): Client
    {
        return new Client($this->getUri($config));
    }

    public function collection(array $config): Collection
    {
        $client = new Client($this->getUri($config));
        return $client->selectCollection($config['database'], $config['collection']);
    }

    final protected function getUri(array $config): string
    {
        if (!$config['username']) {
            return 'mongodb://' . $config['host'] . ':' . $config['port'];
        } else {
            return 'mongodb://' . $config['username'] . ':' . $config['password'] . '@' . $config['host'] . ':' . $config['port'];
        }
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
        $res = $this->manager($config)->executeBulkWrite($this->namespace($config), $bulkWrite, ['writeConcern' => $this->writeConcern($timeout)]);
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
        $res = $this->manager($config)->executeBulkWrite($this->namespace($config), $bulkWrite, ['writeConcern' => $this->writeConcern($timeout)]);
        return [
            'confirm' => $res->isAcknowledged(),
            'error' => $res->getWriteConcernError(),
            'error_arr' => $res->getWriteErrors(),
            'matched' => $res->getMatchedCount(),
            'modified' => $res->getModifiedCount(),
        ];
    }

    /**
     * 更新或插入
     * @Task(timeout=30)
     * @param array $config
     * @param array $filter
     * @param array $document
     * @param array $default
     * @param int $timeout
     * @return array
     */
    public function upsert(array $config, array $filter, array $document, array $default = [], int $timeout = 1000): array
    {
        $bulkWrite = $this->bulkWrite();
        $bulkWrite->update($filter,
            [
                '$set' => $document,
                '$setOnInsert' => $default,
            ],
            ['multi' => true, 'upsert' => true]);
        $res = $this->manager($config)->executeBulkWrite($this->namespace($config), $bulkWrite, ['writeConcern' => $this->writeConcern($timeout)]);
        return [
            'confirm' => $res->isAcknowledged(),
            'error' => $res->getWriteConcernError(),
            'error_arr' => $res->getWriteErrors(),
            'matched' => $res->getMatchedCount(),
            'modified' => $res->getModifiedCount(),
        ];
    }

    /**
     * 自增减
     * @param array $config
     * @param array $filter
     * @param array $document
     * @param int $timeout
     * @return array
     */
    public function inc(array $config, array $filter, array $document, int $timeout = 1000): array
    {
        $bulkWrite = $this->bulkWrite();
        $bulkWrite->update($filter, ['$inc' => $document], ['multi' => true, 'upsert' => false]);
        $res = $this->manager($config)->executeBulkWrite($this->namespace($config), $bulkWrite, ['writeConcern' => $this->writeConcern($timeout)]);
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
    public
    function delete(array $config, array $filter, int $timeout = 1000): array
    {
        $bulkWrite = $this->bulkWrite();
        $bulkWrite->delete($filter);
        $res = $this->manager($config)->executeBulkWrite($this->namespace($config), $bulkWrite, ['writeConcern' => $this->writeConcern($timeout)]);
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
    public
    function query(array $config, array $filter, array $options = []): \Hyperf\Utils\Collection
    {
        $query = new Query($filter, $options);
        $readPreference = new ReadPreference(ReadPreference::RP_PRIMARY);
        $res = $this->manager($config)->executeQuery($this->namespace($config), $query, $readPreference);
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
    public
    function count(array $config, array $filter = [], array $options = []): int
    {
        return $this->collection($config)->countDocuments($filter, $options);
    }
}