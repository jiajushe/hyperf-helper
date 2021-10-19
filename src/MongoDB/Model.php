<?php

namespace Jiajushe\HyperfHelper\MongoDB;


use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Str;
use Jiajushe\HyperfHelper\Exception\CustomError;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Session;
use MongoDB\Driver\WriteConcern;

abstract class Model
{
    protected Manager $manager;
    protected string $uri;
    protected Session $session;
    protected string $connection;
    protected array $config;
    protected string $database;
    protected string $collection;
    protected string $namespace;

    /**
     * @Inject
     * @var ModelTask
     */
    protected ModelTask $modelTask;

    public function __construct()
    {
        $this->getManager();
    }

    /**
     * @return Manager
     * @author yun 2021-10-19 11:28:21
     */
    final protected function getManager(): Manager
    {
        $config = $this->getConfig();
        if (!isset($this->manager)) {
            if (!$config['username']) {
                $uri = 'mongodb://' . $config['host'] . ':' . $config['port'];
            } else {
                $uri = 'mongodb://' . $config['username'] . ':' . $config['password'] . '@' . $config['host'] . ':' . $config['port'];
            }
            $this->uri = $uri;
            $this->manager = new Manager($uri);
        }
        return $this->manager;
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
    public function getNamespace(): string
    {
        if (!isset($this->namespace)) {
            $this->namespace = $this->getDatabase() . '.' . $this->getCollection();
        }
        return $this->namespace;
    }

    /**
     * @return Session
     */
    final public function startTransaction(): Session
    {
        if (!isset($this->session)) {
            $this->session = $this->manager->startSession(['causalConsistency' => true]);
        }
        return $this->session;
    }

    /**
     * @param Session $session
     * @return Model
     * @throws CustomError
     */
    final public function setSession(Session $session): Model
    {
        if (isset($this->session)) {
            throw new CustomError('session was set');
        }
        $this->session = $session;
        return $this;
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
     * @param array $document
     * @return array
     */
    public function create(array $document): array
    {
        $bulkWrite = $this->bulkWrite();
        $bulkWrite->insert($document);
        return $this->modelTask->write(
            $this->uri,
            $this->getNamespace(),
            $bulkWrite,
            ['writeConcern' => $this->writeConcern()]);
    }
}