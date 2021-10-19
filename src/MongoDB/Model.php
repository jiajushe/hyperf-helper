<?php

namespace Jiajushe\HyperfHelper\MongoDB;


use Hyperf\Contract\ConfigInterface;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Str;
use MongoDB\Driver\Manager;

abstract class Model
{
    protected Manager $manager;
    private $session;
    protected string $connection;
    protected array $config;
    protected string $database;
    protected string $collection;
    protected string $namespace;

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

    final private function setSession()
    {
        $this->session = $this->manager->startSession(['causalConsistency' => true]);
    }

    final public function startTransaction()
    {
        if (!isset($this->session)) {
            $this->setSession();
        }
        return $this->session;
    }


}