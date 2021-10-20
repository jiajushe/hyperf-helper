<?php

namespace Jiajushe\HyperfHelper\MongoDB;


use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Collection;
use Hyperf\Utils\Str;
use Jiajushe\HyperfHelper\Exception\CustomError;
use MongoDB\Driver\Exception\Exception;

abstract class Model
{
    /**
     * @var string  连接配置名称
     */
    protected string $connection;

    /**
     * @var array   连接配置
     */
    protected array $config;

    /**
     * @var string  表名
     */
    protected string $collection;

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
    /**
     * @var array 查询表达式
     */
    protected array $filter = [];

    /**
     * @var array|int[] 筛选字段
     */
    protected array $projection = ['_id' => 1];

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

    public function select(array $field_arr, bool $choose = true): Model
    {
        foreach ($field_arr as $field) {
            $this->projection[$field] = (int)$choose;
        }
        return $this;
    }

    /**
     * 查询多条
     * @return Collection
     * @throws Exception
     */
    public function all(): Collection
    {
        $res = $this->modelTask->query(
            $this->config,
            $this->filter,
            [
                'projection' => $this->projection,
            ]
        );
        $this->resetAfterQuery();
        return $res;
    }

    /**
     * @throws CustomError
     */
    public function where($field, string $operator = '', $value = ''): Model
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

    /**
     * @return array|int[]
     */
    public function getProjection(): array
    {
        return $this->projection;
    }

    /**
     * 查询后重置
     */
    final protected function resetAfterQuery()
    {
        $this->projection = ['_id' => 1];
        $this->filter = [];
    }


}