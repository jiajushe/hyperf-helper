<?php

namespace Jiajushe\HyperfHelper\MongoDB;

use Hyperf\Task\Annotation\Task;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Manager;

//use MongoDB\Driver\WriteResult;

class ModelTask
{
//    /**
//     * @var Manager
//     */
//    public $manager;
//
//    /**
//     * @Task
//     */
//    public function insert(string $namespace, array $document)
//    {
//        $writeConcern = new WriteConcern(WriteConcern::MAJORITY, 1000);
//        $bulk = new BulkWrite();
//        $bulk->insert($document);
//
//        $result = $this->manager()->executeBulkWrite($namespace, $bulk, $writeConcern);
//        return $result->getUpsertedCount();
//    }
//
//    /**
//     * @Task
//     */
//    public function query(string $namespace, array $filter = [], array $options = [])
//    {
//        $query = new Query($filter, $options);
//        $cursor = $this->manager()->executeQuery($namespace, $query);
//        return $cursor->toArray();
//    }
//
//    protected function manager()
//    {
//        if ($this->manager instanceof Manager) {
//            return $this->manager;
//        }
//        $uri = 'mongodb://127.0.0.1:27017';
//        return $this->manager = new Manager($uri, []);
//    }

    /**
     * @Task(timeout=10)
     * @param Manager $manager
     * @param string $namespace
     * @param BulkWrite $bulkWrite
     * @param array $options
     * @return array
     */
    public function write(Manager $manager, string $namespace, BulkWrite $bulkWrite, array $options): array
    {
        $res = $manager->executeBulkWrite($namespace, $bulkWrite, $options);
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