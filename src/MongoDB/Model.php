<?php

namespace Jiajushe\HyperfHelper\MongoDB;


use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Str;
use Jiajushe\HyperfHelper\Exception\CustomError;
use MongoDB\Driver\Exception\Exception;

abstract class Model
{
    /**
     * 连接配置名称
     * @var string
     */
    protected string $connection;
    /**
     * 连接配置
     * @var array
     */
    protected array $config;
    /**
     * 表名
     * @var string
     */
    protected string $collection;

    protected array $filter = [];
    protected const OPERATORS = [
        '!=' => '$ne',
        '<>' => '$ne',
        '>' => '$gt',
        '>=' => '$gte',
        '<' => '$lt',
        '<=' => '$lte',
        'in' => '$in',
        'not' => '$not',
        'between' => ['$gte', '$lte'],
    ];

    protected array $projection = [];

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
     * 获取表名
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
     * 插入单条
     * @param array $document
     * @param int $timeout
     * @return array
     */
    public function create(array $document, int $timeout = 1000): array
    {
        return $this->modelTask->insert($this->config, [$document], $timeout);
    }

    /**
     * 插入多条
     * @param array $document
     * @param int $timeout
     * @return array
     */
    public function insert(array $document, int $timeout = 1000): array
    {
        return $this->modelTask->insert($this->config, $document, $timeout);
    }

    public function select(array $field_arr, int $choose = 1): Model
    {
        foreach ($field_arr as $field) {
            $this->projection[$field] = $choose;
        }
        return $this;
    }

    /**
     * 查询多条
     * @return array
     * @throws Exception
     */
    public function all(): array
    {
        return $this->modelTask->query($this->config, $this->filter);
    }

    /**
     * @throws CustomError
     */
    public function where($field, string $operator, $value): Model
    {
        if (is_array($field)) {
            foreach ($field as $item) {
                $this->where(...$item);
            }
            return $this;
        }
        if (is_string($field) && $operator === '=') {
            $this->filter[$field] = $value;
            return $this;
        }
        if (!empty(self::OPERATORS[$operator])) {
            if ($operator == 'between') {
                if (!is_array($value)) {
                    throw new CustomError('$value must be array');
                }
                $this->filter[$field] = [self::OPERATORS[0] => $value[0], self::OPERATORS[1] => $value[1]];
                return $this;
            }
            if (in_array($operator, ['in', 'not']) && !is_array($value)) {
                throw new CustomError('$value must be array');
            }
            $this->filter[$field] = [self::OPERATORS[$operator] => $value];
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getFilter(): array
    {
        return $this->filter;
    }


}