<?php

namespace Jiajushe\HyperfHelper\MongoDB;


use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Collection;
use Hyperf\Utils\Str;
use Jiajushe\HyperfHelper\Exception\CustomError;
use MongoDB\BSON\ObjectId;
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
        '=' => '$eq',
        '!=' => '$ne',
        '<>' => '$ne',
        '>' => '$gt',
        '>=' => '$gte',
        '<' => '$lt',
        '<=' => '$lte',
        'in' => '$in',
        'between' => ['$gte', '$lte'],
    ];

    /**
     * @var array 查询表达式
     */
    protected array $filter = [];

    protected const PROJECTION_OPT = 'projection';
    protected const LIMIT_OPT = 'limit';
    protected const SKIP_OPT = 'skip';
    protected const SORT_OPT = 'sort';

    protected string $created_at = 'created_at';
    protected string $updated_at = 'updated_at';

    /**
     * @var array   查询选项
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
     * @return Model
     */
    final public function resetOptions(): Model
    {
        $this->options = [
            self::PROJECTION_OPT => [], //筛选字段
            self::LIMIT_OPT => 0,   //返回的最大条数
            self::SKIP_OPT => 0,    //跳过多少条
            self::SORT_OPT => [],   //排序
        ];
        return $this;
    }

    /**
     * 重置查询表达式
     * @return $this
     */
    final public function resetFilter(): Model
    {
        $this->filter = [];
        return $this;
    }

    /**
     * 获取表名
     * @return string
     */
    final public function getCollection(): string
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
    final public function create(array $document, int $timeout = 1000): array
    {
        $document = $this->addTime([$document]);
        return $this->modelTask->insert($this->config, [$document], $timeout);
    }

    /**
     * 插入多条
     * @param array $document
     * @param int $timeout
     * @return array
     */
    final public function insert(array $document, int $timeout = 1000): array
    {
        $document = $this->addTime($document);
        return $this->modelTask->insert($this->config, $document, $timeout);
    }

    final protected function addTime(array $document): array
    {
        if (!$this->created_at && !$this->updated_at) {
            return $document;
        }
        $time = time();
        foreach ($document as $key => $value) {
            if ($this->created_at && empty($value[$this->created_at])) {
                $document[$key][$this->created_at] = $time;
            }
            if ($this->updated_at && empty($value[$this->updated_at])) {
                $document[$key][$this->updated_at] = $time;
            }
        }
        return $document;
    }

    /**
     * 查询多条
     * @return Collection
     * @throws Exception
     */
    final public function all(): Collection
    {
        return $this->modelTask->query($this->config, $this->filter, $this->options);
    }

    /**
     * 查询一条
     * @param string|null $id
     * @return mixed
     * @throws CustomError
     * @throws Exception
     */
    final public function find(string $id = null)
    {
        if ($id) {
            $this->where('id', '=', $id);
        }
        $this->options[self::LIMIT_OPT] = 1;
        $res = $this->modelTask->query($this->config, $this->filter, $this->options);
        return $res->first();
    }

    /**
     * 分页
     * @param int $page
     * @param int $limit
     * @return array
     * @throws Exception
     */
    final public function paginate(int $page = 1, int $limit = 15): array
    {
        $total = $this->count();
        $skip = ($page - 1) * $limit;
        $total_page = (int)ceil($total / $limit);
        $this->skip($skip)->limit($limit);
        $data = $this->limit($limit)->skip($skip)->all();
        return [
            'total' => $total,
            'total_page' => $total_page,
            'current_page' => $page > $total_page ? $total_page + 1 : $page,
            'pre_page' => $limit,
            'data' => $data
        ];
    }

    /**
     * 更新
     * @param array $document
     * @param int $timeout
     * @return array
     */
    final public function update(array $document, int $timeout = 1000): array
    {
        $document = $this->addUpdated($document);
        return $this->modelTask->update($this->config, $this->filter, $document, $timeout);
    }

    final protected function addUpdated(array $document): array
    {
        if (!$this->updated_at) {
            return $document;
        }
        if (empty($document[$this->updated_at])) {
            $document[$this->updated_at] = time();
        }
        return $document;
    }

    /**
     * 删除
     * @param string|null $id
     * @param int $timeout
     * @return array
     * @throws CustomError
     */
    final public function delete(string $id = null, int $timeout = 1000): array
    {
        if ($id) {
            $this->where('id', '=', $id);
        }
        return $this->modelTask->delete($this->config, $this->filter, $timeout);
    }

    /**
     * 获取数据条数
     * @return int
     */
    final public function count(): int
    {
        if (!$this->options[self::LIMIT_OPT]) {
            unset($this->options[self::LIMIT_OPT]);
        }
        if (!$this->options[self::SKIP_OPT]) {
            unset($this->options[self::SKIP_OPT]);
        }
        return $this->modelTask->count($this->config, $this->filter, $this->options);
    }

    /**
     * 筛选字段
     * @param array $field_arr 字段数组
     * @param bool $choose 是否选择
     * @return $this
     */
    final public function select(array $field_arr, bool $choose = true): Model
    {
        foreach ($field_arr as $field) {
            if ($field === 'id') {
                $field = '_id';
            }
            $this->options[self::PROJECTION_OPT][$field] = (int)$choose;
        }
        return $this;
    }

    /**
     * @throws CustomError
     */
    final public function where($field, string $operator = '', $value = ''): Model
    {
        if (is_array($field)) {
            foreach ($field as $item) {
                $this->where(...$item);
            }
            return $this;
        }
        if ($field === 'id') {
            $field = '_id';
            $value = new ObjectId($value);
        }
        if (!empty(self::OPERATORS[$operator])) {
            if ($operator == 'between') {
                if (!is_array($value)) {
                    throw new CustomError('$value must be array');
                }
                $this->filter[$field] = [self::OPERATORS[0] => $value[0], self::OPERATORS[1] => $value[1]];
                return $this;
            }
            if ($operator == 'in' && !is_array($value)) {
                throw new CustomError('$value must be array');
            }
            $this->filter[$field] = [self::OPERATORS[$operator] => $value];
        }
        return $this;
    }

    /**
     * 排序
     * @param $field
     * @param string $opt
     * @return $this
     */
    final public function sort($field, string $opt = 'asc'): Model
    {
        $opt_arr = ['asc' => 1, 'desc' => -1];
        if (is_array($field)) {
            foreach ($field as $key => $val) {
                if (is_int($key)) {
                    $this->options[self::SORT_OPT][$val] = $opt_arr['asc'];
                } else {
                    $this->options[self::SORT_OPT][$key] = $opt_arr[$val];
                }
            }
        } else {
            $this->options[self::SORT_OPT][$field] = $opt_arr[$opt];
        }
        return $this;
    }

    /**
     * @param int $num
     * @return $this
     */
    final public function skip(int $num): Model
    {
        $this->options[self::SKIP_OPT] = $num;
        return $this;
    }

    /**
     * @param int $num
     * @return $this
     */
    final public function limit(int $num): Model
    {
        $this->options[self::LIMIT_OPT] = $num;
        return $this;
    }

    /**
     * @return array
     */
    final public function getFilter(): array
    {
        return $this->filter;
    }

    /**
     * @return array|int[]
     */
    final public function getOptions(): array
    {
        return $this->options;
    }


}