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
     * @Inject
     * @var ModelTask
     */
    protected ModelTask $modelTask;

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
    protected const PROJECTION_FIELD = 'projection';
    protected const LIMIT_FIELD = 'limit';
    protected const SKIP_FIELD = 'skip';


    /**
     * @var array   选项
     */
    protected array $options = [];

    public function __construct()
    {
        if (!isset($this->connection)) {
            $this->connection = 'default';
        }
        $configObj = ApplicationContext::getContainer()->get(ConfigInterface::class);
        $config = $configObj->get('mongodb.' . $this->connection);
        $config['collection'] = $this->getCollection();
        $this->config = $config;
        $this->resetOptions();
    }

    /**
     * 重置查询选项
     */
    final public function resetOptions()
    {
        $this->options = [
            self::PROJECTION_FIELD => [],
            self::LIMIT_FIELD => null,
            self::SKIP_FIELD => 0,
        ];
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

    /**
     * 筛选字段
     * @param array $field_arr 字段数组
     * @param bool $choose 是否选择
     * @return $this
     */
    public function select(array $field_arr, bool $choose = true): Model
    {
        foreach ($field_arr as $field) {
            $this->options[self::PROJECTION_FIELD][$field] = (int)$choose;
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
        $res = $this->modelTask->query($this->config, $this->filter, $this->options);
        $this->resetOptions();
        return $res;
    }

    /**
     * 查询一条
     * @param string|null $_id
     * @return mixed
     * @throws CustomError
     * @throws Exception
     */
    public function find(string $_id = null)
    {
        if ($_id) {
            $this->where('_id', '=', $_id);
        }
        $this->options[self::LIMIT_FIELD] = 1;
        $res = $this->modelTask->query($this->config, $this->filter, $this->options);
        $this->resetOptions();
        return $res->first();
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
        return $this->options[self::PROJECTION_FIELD];
    }


}