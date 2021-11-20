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
use Traversable;

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
        'not_in' => '$nin',
        'all' => '$all',
        'not' => '$not',
        'between' => ['$gte', '$lte'],
        'like' => 'xsi',  //不分大小写匹配所有
        'like_before' => 'xsi', //不分大小写匹配开头
        'like_after' => 'xsi', //不分大小写匹配结尾
    ];

    /**
     * @var array 查询表达式
     */
    protected array $filter = [];

    /**
     * @var array 聚合查询表达式
     */
    protected array $pipeline = [];

    protected array $objectId_field = [];

    protected const PROJECTION_OPT = 'projection';
    protected const LIMIT_OPT = 'limit';
    protected const SKIP_OPT = 'skip';
    protected const SORT_OPT = 'sort';

    public string $created_at = 'created_at';
    public string $updated_at = 'updated_at';

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
        $this->resetFilter();
        $this->resetPipeline();
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
        $this->filter = ['$and' => []];
        return $this;
    }

    /**
     * 重置聚合查询表达式
     * @return $this
     */
    final public function resetPipeline(): Model
    {
        $this->pipeline = [];
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
    final public function create(array $document, int $timeout = 30000): array
    {
        $document = $this->changeObjectId($document);
        $document = $this->setAttr($document);
        $document = $this->addTime([$document]);
        return $this->modelTask->insert($this->config, $document, $timeout);
    }

    /**
     * 插入多条
     * @param array $document
     * @param int $timeout
     * @return array
     */
    final public function insert(array $document, int $timeout = 30000): array
    {
        foreach ($document as $index => $item) {
            $document[$index] = $this->changeObjectId($item);
            $document[$index] = $this->setAttr($item);
        }
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
        $pipeline = $this->getPipeline();
        if ($pipeline) {
            $res = $this->modelTask->aggregate($this->config, $pipeline);
        } else {
            $res = $this->modelTask->query($this->config, $this->getFilter(), $this->options);
        }
        $this->resetFilter();
        $this->resetOptions();
        $this->resetPipeline();
        return $res;
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
        $pipeline = $this->getPipeline();
        $filter = $this->getFilter();
        if ($pipeline) {
            if ($filter) {
                $pipeline[] = ['$match' => $filter];
            }
            $pipeline[] = ['$limit' => 1];
            $res = $this->modelTask->aggregate($this->config, $pipeline);
        } else {
            $this->options[self::LIMIT_OPT] = 1;
            $res = $this->modelTask->query($this->config, $filter, $this->options);
        }
        $this->resetFilter();
        $this->resetPipeline();
        $this->resetOptions();
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
        $total = $this->count(false);
        $skip = ($page - 1) * $limit;
        $total_page = (int)ceil($total / $limit);
        $this->skip($skip)->limit($limit);
        $data = $this->limit($limit)->skip($skip)->all();
        return [
            'total' => $total,
            'total_page' => $total_page,
            'page' => $page > $total_page ? $total_page + 1 : $page,
            'limit' => $limit,
            'data' => $data
        ];
    }

    /**
     * 更新
     * @param array $document
     * @param int $timeout
     * @return array
     * @throws CustomError
     */
    final public function update(array $document, int $timeout = 30000): array
    {
        $document = $this->setAttr($document);
        $document = $this->addUpdated($document);
        if (isset($document['id'])) {
            $this->where('id', '=', $document['id']);
            unset($document['id']);
        }
        $document = $this->changeObjectId($document);
        $res = $this->modelTask->update($this->config, $this->getFilter(), $document, $timeout);
        $this->resetOptions();
        $this->resetFilter();
        return $res;
    }

    /**
     * 更新或插入
     * @param array $document
     * @param array $default
     * @param int $timeout
     * @return array
     * @throws CustomError
     */
    final public function upsert(array $document, array $default = [], int $timeout = 30000): array
    {
        $document = $this->setAttr($document);
        $document = $this->addUpdated($document);
        if (isset($document['id'])) {
            $this->where('id', '=', $document['id']);
            unset($document['id']);
        }
        $document = $this->changeObjectId($document);
        $res = $this->modelTask->upsert($this->config, $this->getFilter(), $document, $default, $timeout);
        $this->resetOptions();
        $this->resetFilter();
        return $res;
    }

    /**
     * 自增减
     * @param array $document [filed => num, ...]
     * @param int $timeout
     * @return array
     * @throws CustomError
     */
    final public function inc(array $document, int $timeout = 30000): array
    {
        $document = $this->addUpdated($document);
        if (isset($document['id'])) {
            $this->where('id', '=', $document['id']);
            unset($document['id']);
        }
        $res = $this->modelTask->inc($this->config, $this->getFilter(), $document, $timeout);
        $this->resetOptions();
        $this->resetFilter();
        return $res;
    }

    /**
     * 添加更新时间
     * @param array $document
     * @return array
     */
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
    final public function delete(string $id = null, int $timeout = 30000): array
    {
        if ($id) {
            $this->where('id', '=', $id);
        }
        $res = $this->modelTask->delete($this->config, $this->getFilter(), $timeout);
        $this->resetOptions();
        $this->resetFilter();
        return $res;
    }

    /**
     * 获取数据条数
     * @param bool $reset
     * @return int
     */
    final public function count(bool $reset = true): int
    {
        if (!$this->options[self::LIMIT_OPT]) {
            unset($this->options[self::LIMIT_OPT]);
        }
        if (!$this->options[self::SKIP_OPT]) {
            unset($this->options[self::SKIP_OPT]);
        }
        $res = $this->modelTask->count($this->config, $this->getFilter(), $this->options);
        if ($reset) {
            $this->resetFilter();
            $this->resetOptions();
        }
        return $res;
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
     * 筛选字段
     * @param array $field_arr 字段数组
     * @param bool $choose 是否选择
     * @return $this
     */
    final public function select(array $field_arr, bool $choose = true): Model
    {
        foreach ($field_arr as $field) {
            $this->options[self::PROJECTION_OPT][$this->idTo_id($field)] = (int)$choose;
        }
        return $this;
    }

    /**
     * @param mixed $field
     * @param string $operator
     * @param mixed $value
     * @return Model
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
        if ($this->objectId_field && in_array($field, $this->objectId_field)) {
            $value = $this->getObjectId($value);
        }
        if ($field === 'id') {
            $field = '_id';
            $value = $this->getObjectId($value);
        }
        if (!empty(self::OPERATORS[$operator])) {
            if ($operator === 'between') {
                if (!is_array($value)) {
                    throw new CustomError('$value must be array');
                }
                $this->filter['$and'][][$field] = [self::OPERATORS['between'][0] => $value[0], self::OPERATORS['between'][1] => $value[1]];
                return $this;
            }
            if ($operator === 'like') {
                $this->filter['$and'][][$field] = ['$regex' => $value, '$options' => self::OPERATORS[$operator]];
                return $this;
            }
            if ($operator === 'like_before') {
                $this->filter['$and'][][$field] = ['$regex' => '^' . $value, '$options' => self::OPERATORS[$operator]];
                return $this;
            }
            if ($operator === 'like_after') {
                $this->filter['$and'][][$field] = ['$regex' => $value . '$', '$options' => self::OPERATORS[$operator]];
                return $this;
            }
            if (in_array($operator, ['in', 'not_in', 'all']) && !is_array($value)) {
                throw new CustomError('$value must be array');
            }
            $this->filter['$and'][][$field] = [self::OPERATORS[$operator] => $value];
        }
        return $this;
    }

    /**
     * @param array $conditions [ [field, operator, value], ...]
     * @return Model
     * @throws CustomError
     */
    final public function whereOr(array $conditions): Model
    {
        $filter = [];
        foreach ($conditions as $condition) {
            if ($this->objectId_field && in_array($condition[0], $this->objectId_field)) {
                $condition[2] = $this->getObjectId($condition[2]);
            }
            if ($condition[0] === 'id') {
                $condition[0] = '_id';
                $condition[2] = $this->getObjectId($condition[2]);
            }
            if ($condition[1] === 'between') {
                if (!is_array($condition[2])) {
                    throw new CustomError('$value must be array');
                }
                $filter['$or'][][$condition[0]] = [self::OPERATORS['between'][0] => $condition[2][0], self::OPERATORS['between'][1] => $condition[2][1]];
                continue;
            }
            if ($condition[1] === 'like') {
                $filter['$or'][][$condition[0]] = ['$regex' => $condition[2], '$options' => self::OPERATORS[$condition[1]]];
                continue;
            }
            if ($condition[1] === 'like_before') {
                $filter['$or'][][$condition[0]] = ['$regex' => '^' . $condition[2], '$options' => self::OPERATORS[$condition[1]]];
                continue;
            }
            if ($condition[1] === 'like_after') {
                $filter['$or'][][$condition[0]] = ['$regex' => $condition[2] . '$', '$options' => self::OPERATORS[$condition[1]]];
                continue;
            }
            if (in_array($condition[1], ['in', 'not_in', 'all']) && !is_array($condition[2])) {
                throw new CustomError('$value must be array');
            }
            $filter['$or'][][$condition[0]] = [self::OPERATORS[$condition[1]] => $condition[2]];
        }
        $this->filter['$and'][] = $filter;
        return $this;
    }

    /**
     * @param array $conditions [ [field, operator, value], ...]
     * @return Model
     * @throws CustomError
     */
    final public function whereNotOr(array $conditions): Model
    {
        $filter = [];
        foreach ($conditions as $condition) {
            if ($this->objectId_field && in_array($condition[0], $this->objectId_field)) {
                $condition[2] = $this->getObjectId($condition[2]);
            }
            if ($condition[0] === 'id') {
                $condition[0] = '_id';
                $condition[2] = $this->getObjectId($condition[2]);
            }
            if ($condition[1] === 'between') {
                if (!is_array($condition[2])) {
                    throw new CustomError('$value must be array');
                }
                $filter['$nor'][][$condition[0]] = [self::OPERATORS['between'][0] => $condition[2][0], self::OPERATORS['between'][1] => $condition[2][1]];
                continue;
            }
            if ($condition[1] === 'like') {
                $filter['$nor'][][$condition[0]] = ['$regex' => $condition[2], '$options' => self::OPERATORS[$condition[1]]];
                continue;
            }
            if ($condition[1] === 'like_before') {
                $filter['$nor'][][$condition[0]] = ['$regex' => '^' . $condition[2], '$options' => self::OPERATORS[$condition[1]]];
                continue;
            }
            if ($condition[1] === 'like_after') {
                $filter['$nor'][][$condition[0]] = ['$regex' => $condition[2] . '$', '$options' => self::OPERATORS[$condition[1]]];
                continue;
            }
            if (in_array($condition[1], ['in', 'not_in', 'all']) && !is_array($condition[2])) {
                throw new CustomError('$value must be array');
            }
            $filter['$nor'][][$condition[0]] = [self::OPERATORS[$condition[1]] => $condition[2]];
        }
        $this->filter['$and'][] = $filter;
        return $this;
    }

    /**
     * @param array $filter
     * @return Model
     */
    final public function whereRaw(array $filter): Model
    {
        $this->filter['$and'][] = $filter;
        return $this;
    }

    /**
     * for aggregate
     * @param array $lookup
     * [
     *  'from' => 'collection_name',
     *  'localField' => 'expo_id',
     *  'foreignField' => '_id',
     *  'as' => 'collection_name_items',
     * ]
     * @return $this
     */
    final public function join(array $lookup): Model
    {
        $pipeline = $this->pipeline;
        $pipeline[] = ['$lookup' => $lookup];
        $this->pipeline = $pipeline;
        return $this;
    }

    /**
     * for aggregate
     * 多种写法，要查文档
     * @param array $group
     * @return $this
     */
    final public function group(array $group): Model
    {
        $pipeline = $this->pipeline;
        $pipeline[] = ['$group' => $group];
        $this->pipeline = $pipeline;
        return $this;
    }

    /**
     * for aggregate　选择字段
     * ['filed1'=>1,'filed2'=>0]
     */
    final public function project(array $project): Model
    {
        $pipeline = $this->pipeline;
        $pipeline[] = ['$project' => $project];
        $this->pipeline = $pipeline;
        return $this;
    }

    /**
     * for aggregate　排序
     * ['filed1'=>1,'filed2'=>0]
     */
    final public function order(array $sort): Model
    {
        $pipeline = $this->pipeline;
        $pipeline[] = ['$sort' => $sort];
        $this->pipeline = $pipeline;
        return $this;
    }

    /**
     * for aggregate　where
     */
    final public function match(array $match): Model
    {
        $pipeline = $this->pipeline;
        $pipeline[] = ['$match' => $match];
        $this->pipeline = $pipeline;
        return $this;
    }

    /**
     * 判断字段是否存在
     * @param array $field_arr ['field' => bool]
     * @return $this
     */
    final public function exists(array $field_arr): Model
    {
        foreach ($field_arr as $index => $item) {
            $this->filter['$and'][][$this->idTo_id($this->idTo_id($index))] = ['$exists' => $item];
        }
        return $this;
    }

    /**
     * 排序
     * @param mixed $field
     * @param string $opt
     * @return $this
     */
    final public function sort($field, string $opt = 'asc'): Model
    {
        $opt_arr = ['asc' => 1, 'desc' => -1];
        if (is_array($field)) {
            foreach ($field as $key => $val) {
                if (is_int($key)) {
                    $this->options[self::SORT_OPT][$this->idTo_id($val)] = $opt_arr['asc'];
                } else {
                    $this->options[self::SORT_OPT][$this->idTo_id($key)] = $opt_arr[$val];
                }
            }
        } else {
            $this->options[self::SORT_OPT][$this->idTo_id($field)] = $opt_arr[$opt];
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
     * @return array
     */
    final public function getFilter(): array
    {
        $filter = $this->filter;
        if (empty($filter['$and'])) {
            $filter = [];
        }
        return $filter;
    }

    /**
     * @return array|int[]
     */
    final public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @return array
     */
    public function getPipeline(): array
    {
        return $this->pipeline;
    }

    final public function changeTime(array $data, string $format = 'Y-m-d H:i:s'): array
    {
        $created_at = $this->created_at;
        $updated_at = $this->updated_at;
        foreach ($data as $item) {
            if ($created_at && isset($item->$created_at)) {
                $item->$created_at = date($format, $item->$created_at);
            }
            if ($updated_at && isset($item->$updated_at)) {
                $item->$updated_at = date($format, $item->$updated_at);
            }
        }
        return $data;
    }

    /**
     * 转换为objectId
     * @param array $document
     * @return array
     */
    final protected function changeObjectId(array $document): array
    {
        foreach ($document as $index => $item) {
            if (in_array($index, $this->objectId_field)) {
                $document[$index] = $this->getObjectId($item);
            }
        }
        return $document;
    }

    final protected function idTo_id(string $field): string
    {
        if ($field == 'id') {
            $field = '_id';
        }
        return $field;
    }

    /**
     *  判断值是否唯一
     * @param array $filter
     * @param string $id
     * @return bool true=>已存在，false=>不存在
     * @throws CustomError
     * @throws Exception
     */
    final public function isUnique(array $filter, string $id = ''): bool
    {
        foreach ($filter as $f => $v) {
            $this->where($f, '=', $v);
        }
        if ($id !== '') {
            $this->where('id', '!=', $id);
        }
        return (bool)$this->find();
    }

    /**
     * 字段过滤
     * @param array $needs 要保留的字段
     * @param array $inputs
     * @return array
     */
    final public function filterFiled(array $needs, array $inputs): array
    {
        foreach ($inputs as $index => $input) {
            if (!in_array($index, $needs)) {
                unset($inputs[$index]);
            }
        }
        return $inputs;
    }

    public function __call($name, $arguments)
    {
        return '';
    }

    /**
     * 插入／更新修改器
     * @param array $document
     * @return array
     */
    final protected function setAttr(array $document): array
    {
        foreach ($document as $index => $value) {
            $method = 'set' . Str::ucfirst(Str::camel($index)) . 'Attr';
            if (method_exists($this, $method)) {
                $document[$index] = $this->{$method}($value, $document);
            }
        }
        return $document;
    }

    /**
     * 追加字段.
     * @param object|array $data
     * @param array $field
     * @return array|object
     */
    final public function append($data, array $field)
    {
        $changed = [];
        foreach ($field as $item) {
            $changed[$item] = 'get' . Str::ucfirst(Str::camel($item)) . 'Attr';
        }
        if ($data instanceof Collection) {
            $data->each(function ($item) use ($field) {
                return $this->append($item, $field);
            });
        } elseif (is_array($data)){
            foreach ($data as $index => $row) {
                $data[$index] = $this->append($row, $field);
            }
            return $data;
        } else {
            foreach ($changed as $index => $item) {
                if (in_array($index, [$this->created_at, $this->updated_at])) {
                    if (!empty($data->{$index})) {
                        $data->{$index} = $this->getDateStr($data->{$index});
                    }
                } else {
                    $data->{$index} = $this->{$item}($data);
                }
            }
        }
        return $data;
    }

    final public function getDateStr(int $timestamp)
    {
        return date('Y-m-d H:i:s', $timestamp);
    }

    final public function getObjectId($value): ObjectId
    {
        return new ObjectId($value);
    }
}