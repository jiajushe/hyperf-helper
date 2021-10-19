<?php

namespace Jiajushe\HyperfHelper\MongoDB;


use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Str;

abstract class Model
{
    protected array $config;
    protected string $connection;
    protected string $collection;

    /**
     * @Inject
     * @var ModelTask
     */
    protected ModelTask $modelTask;

    public function __construct()
    {
        if (!isset($this->connection)) {
            $this->connection = 'default';
        }
        $configObj = ApplicationContext::getContainer()->get(ConfigInterface::class);
        $config = $configObj->get('mongodb.' . $this->connection);
        $config['collection'] = $this->getCollection();
        $this->config = $config;
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
     * @param array $document
     * @param int $timeout
     * @return array
     */
    public function create(array $document, int $timeout = 1000): array
    {
        return $this->modelTask->insert($this->config, [$document], $timeout);
    }

    public function insert(array $document, int $timeout = 1000): array
    {
        return $this->modelTask->insert($this->config, $document, $timeout);
    }

    public function insertNoTask(array $document, int $timeout = 1000): array
    {
        return $this->modelTask->insertNoTask($this->config, $document, $timeout);
    }
}