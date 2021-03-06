<?php

namespace Jiajushe\HyperfHelper\MongoDB;

use Hyperf\Task\Annotation\Task;
use MongoDB\BSON\ObjectId;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Driver\Exception\Exception;
use MongoDB\Driver\Manager;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Command;
use MongoDB\Driver\Query;
use MongoDB\Driver\ReadPreference;
use MongoDB\Driver\WriteConcern;
use MongoDB\Driver\ReadConcern;
use stdClass;
use Throwable;

class ModelTask
{
    final public function manager(array $config): Manager
    {
        return new Manager($this->getUri($config));
    }

    final public function namespace(array $config): string
    {
        return $config['database'] . '.' . $config['collection'];
    }

    final public function client(array $config): Client
    {
        return new Client($this->getUri($config));
    }

    final public function collection(array $config): Collection
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

    final protected function command($document, array $options = []): Command
    {
        return new Command($document, $options);
    }

    /**
     * @param int $timeout
     * @return WriteConcern
     */
    final public function writeConcern(int $timeout = 1000): WriteConcern
    {
        return new WriteConcern(WriteConcern::MAJORITY, $timeout);
    }

    final public function readConcern(): ReadConcern
    {
        return new ReadConcern(ReadConcern::MAJORITY);
    }

    final public function readPreference(): ReadPreference
    {
        return new ReadPreference(ReadPreference::RP_PRIMARY_PREFERRED);
    }

    /**
     * ??????
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
        $res = $this->manager($config)->executeBulkWrite(
            $this->namespace($config),
            $bulkWrite,
            ['writeConcern' => $this->writeConcern($timeout)]);

        return [
            'confirm' => $res->isAcknowledged(),
            'error' => $res->getWriteConcernError(),
            'error_arr' => $res->getWriteErrors(),
            'inserted' => $res->getInsertedCount(),
        ];
    }

    /**
     * ??????
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
        $res = $this->manager($config)->executeBulkWrite(
            $this->namespace($config),
            $bulkWrite,
            ['writeConcern' => $this->writeConcern($timeout)]);
        return [
            'confirm' => $res->isAcknowledged(),
            'error' => $res->getWriteConcernError(),
            'error_arr' => $res->getWriteErrors(),
            'matched' => $res->getMatchedCount(),
            'modified' => $res->getModifiedCount(),
        ];
    }

    /**
     * ???????????????
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
        $new = ['$set' => $document];
        if ($default) {
            $new['$setOnInsert'] = $default;
        }
        $bulkWrite = $this->bulkWrite();
        $bulkWrite->update($filter, $new, ['multi' => true, 'upsert' => true]);
        $res = $this->manager($config)->executeBulkWrite(
            $this->namespace($config),
            $bulkWrite,
            ['writeConcern' => $this->writeConcern($timeout)]);
        return [
            'confirm' => $res->isAcknowledged(),
            'error' => $res->getWriteConcernError(),
            'error_arr' => $res->getWriteErrors(),
            'matched' => $res->getMatchedCount(),
            'modified' => $res->getModifiedCount(),
        ];
    }

    /**
     * ?????????
     * @Task(timeout=30)
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
        $res = $this->manager($config)->executeBulkWrite(
            $this->namespace($config),
            $bulkWrite,
            ['writeConcern' => $this->writeConcern($timeout)]);
        return [
            'confirm' => $res->isAcknowledged(),
            'error' => $res->getWriteConcernError(),
            'error_arr' => $res->getWriteErrors(),
            'matched' => $res->getMatchedCount(),
            'modified' => $res->getModifiedCount(),
        ];
    }

    /**
     * ??????
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
        $res = $this->manager($config)->executeBulkWrite(
            $this->namespace($config),
            $bulkWrite,
            ['writeConcern' => $this->writeConcern($timeout)]);
        return [
            'confirm' => $res->isAcknowledged(),
            'error' => $res->getWriteConcernError(),
            'error_arr' => $res->getWriteErrors(),
            'deleted' => $res->getDeletedCount(),
        ];
    }

    /**
     * ??????
     * @Task(timeout=30)
     * @param array $config
     * @param array $filter
     * @param array $options
     * @return \Hyperf\Utils\Collection
     * @throws Exception
     */
    public function query(array $config, array $filter, array $options = []): \Hyperf\Utils\Collection
    {
        $options['readConcern'] = $this->readConcern();
        $query = new Query($filter, $options);
        $readPreference = $this->readPreference();
        $res = $this->manager($config)->executeQuery($this->namespace($config), $query, ['readPreference' => $readPreference]);
        $res = \Hyperf\Utils\Collection::make($res);
        return $res->each(function ($row) {
            foreach ($row as $index => $item) {
                if ($item instanceof ObjectId) {
                    if ($index == '_id') {
                        $row->id = (string)$item;
                        unset($row->_id);
                    } else {
                        $row->{$index} = (string)$item;
                    }
                }
            }
        });
    }

    /**
     * ??????????????????
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

    /**
     * ?????????????????????
     * @Task (timeout=30)
     * @param array $config
     * @param array $pipeline
     * @param array $options
     * @return \Hyperf\Utils\Collection
     * @throws Exception
     */
    public function aggregate(array $config, array $pipeline, array $options = []): \Hyperf\Utils\Collection
    {
        $options['readConcern'] = $this->readConcern();
        $options['readPreference'] = $this->readPreference();
        $command = $this->command([
            'aggregate' => $config['collection'],
            'pipeline' => $pipeline,
            'cursor' => new stdClass,
        ], $options);
        $res = $this->manager($config)->executeCommand($config['database'], $command)->toArray();
        $res = \Hyperf\Utils\Collection::make($res);
        $join_tables = [];
        foreach ($pipeline as $item) {
            if (!empty($item['$lookup'])) {
                $join_tables[] = $item['$lookup']['as'];
            }
        }
        return $res->each(function ($row) use ($join_tables) {
            foreach ($row as $index => $item) {
                if ($item instanceof ObjectId) {
                    if ($index == '_id') {
                        $row->id = (string)$item;
                        unset($row->_id);
                    } else {
                        $row->{$index} = (string)$item;
                    }
                }
                if (in_array($index, $join_tables)) {
                    if (is_array($row->{$index})) {
                        foreach ($row->{$index} as $key => $join) {
                            foreach ($join as $k => $r) {
                                if ($r instanceof ObjectId) {
                                    if ($k == '_id') {
                                        $row->{$index}[$key]->id = (string)$r;
                                        unset($row->{$index}[$key]->_id);
                                    } else {
                                        $row->{$index}[$key]->{$k} = (string)$r;
                                    }
                                }
                            }
                        }
                    } else {
                        foreach ($row->{$index} as $k => $r) {
                            if ($r instanceof ObjectId) {
                                if ($k == '_id') {
                                    $row->{$index}->id = (string)$r;
                                    unset($row->{$index}->_id);
                                } else {
                                    $row->{$index}->{$k} = (string)$r;
                                }
                            }
                        }
                    }

                }
            }
        });
    }

    /**
     * ???????????????
     * @Task (timeout=30)
     */
    public function transaction(array $data)
    {
        $manager = $this->manager($data[0]['config']);
        $session = $manager->startSession([
            'causalConsistency' => true,
            'defaultTransactionOptions' => [
                'maxCommitTimeMS' => 10000,
                'writeConcern' => $this->writeConcern(10000),
            ],
        ]);
        $session->startTransaction();
        try {
            foreach ($data as $item) {
                $bulkWrite = $this->bulkWrite();
                switch ($item['write']) {
                    case 'insert':
                        foreach ($item['document'] as $row) {
                            $bulkWrite->insert($row);
                        }
                        break;
                    case 'update':
                        $bulkWrite->update($item['filter'],
                            ['$set' => $item['document']],
                            ['multi' => true, 'upsert' => false]);
                        break;
                    case 'upsert':
                        $new = ['$set' => $item['document']];
                        if ($item['default']) {
                            $new['$setOnInsert'] = $item['default'];
                        }
                        $bulkWrite->update($item['filter'], $new, ['multi' => true, 'upsert' => true]);
                        break;
                    case 'inc':
                        $bulkWrite->update($item['filter'], ['$inc' => $item['document']], ['multi' => true, 'upsert' => false]);
                        break;
                    case 'delete':
                        $bulkWrite->delete($item['filter']);
                        break;
                }
                $manager->executeBulkWrite(
                    $this->namespace($item['config']),
                    $bulkWrite,
                    ['session' => $session]);
            }
            $session->commitTransaction();
        } catch (Throwable $t) {
            $session->abortTransaction();
        }

    }
}