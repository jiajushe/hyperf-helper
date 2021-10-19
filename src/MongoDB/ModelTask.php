<?php

namespace Jiajushe\HyperfHelper\MongoDB;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Task\Annotation\Task;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Str;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Manager;
use MongoDB\Driver\WriteConcern;
use MongoDB\Driver\WriteResult;

abstract class ModelTask
{
    protected Manager $manager;
    protected array $config;
    protected string $database;
    protected string $collection;
    protected string $namespace;


    public function __construct()
    {
        $this->setManager();
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        if (!isset($this->namespace)) {
            $this->namespace = $this->getDatabase() . '.' . $this->getCollection();
        }
        return $this->namespace;
    }

    /**
     * @return string
     */
    public function getCollection(): string
    {
        if (!isset($this->collection)) {
            $this->collection = Str::snake(Str::afterLast(get_class($this), '\\'));
        }
        return $this->collection;
    }

    /**
     * @return string
     */
    public function getDatabase(): string
    {
        if (!isset($this->database)) {
            $config = $this->getConfig();
            $this->database = $config['database'];
        }
        return $this->database;
    }

    /**
     * @return array
     */
    final protected function getConfig(): array
    {
        if (isset($this->config)) {
            return $this->config;
        }
        if (!isset($this->connection)) {
            $this->connection = 'default';
        }
        $configObj = ApplicationContext::getContainer()->get(ConfigInterface::class);
        $this->config = $configObj->get('mongodb.' . $this->connection);
        return $this->config;
    }


    /**
     * @author yun 2021-10-19 11:28:21
     */
    final protected function setManager()
    {
        $config = $this->getConfig();
        if (!$config['username']) {
            $uri = 'mongodb://' . $config['host'] . ':' . $config['port'];
        } else {
            $uri = 'mongodb://' . $config['username'] . ':' . $config['password'] . '@' . $config['host'] . ':' . $config['port'];
        }
        $this->manager = new Manager($uri);
    }

    public function bulk(): BulkWrite
    {
        return new BulkWrite();
    }

    public function writeConcern(int $timeout = 1000): WriteConcern
    {
        return new WriteConcern(WriteConcern::MAJORITY, $timeout);
    }

    /**
     * @Task(timeout=30)
     * @param array $document
     * @return WriteResult
     */
    public function create(array $document): WriteResult
    {
        $bulk = $this->bulk()->insert($document);
        return $this->manager->executeBulkWrite($this->getNamespace(), $bulk, $this->writeConcern());
    }

    /**
     * @Task
     * @param array $document
     * @return WriteResult
     */
    public function insert(array $document): WriteResult
    {
        $bulk = $this->bulk();
        foreach ($document as $row) {
            $bulk->insert($row);
        }
        return $this->manager->executeBulkWrite($this->getNamespace(), $bulk, $this->writeConcern(3000));
    }

    public static function __callStatic($name, $arguments)
    {
        // TODO: Implement __callStatic() method.
        pp($name,$arguments);
        return self::$name(...$arguments);
    }
}