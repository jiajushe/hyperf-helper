<?php

namespace Jiajushe\HyperfHelper\MongoDB;


use Hyperf\Contract\ConfigInterface;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Str;
use MongoDB\Driver\Session;

abstract class Model
{
    protected array $config;
    protected string $connection;
    protected string $collection;

    protected ModelTask $modelTask;

    public function __construct()
    {
        $config = $this->getConfig();
        $this->modelTask = new ModelTask($config);
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
        $this->config['collection'] = $this->getCollection();
        return $this->config;
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
        return $this->modelTask->insert([$document],$timeout);
    }
}