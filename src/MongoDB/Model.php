<?php

namespace Jiajushe\HyperfHelper\MongoDB;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Task\Annotation\Task;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Str;
use MongoDB\Client;
use MongoDB\Collection;

class Model
{
    /**
     * @var Client
     */
    protected Client $clientObj;

    /**
     * @var Collection  表的对象
     */
    protected Collection $collectionObj;

    /**
     * @var string  mongodb连接配置名称
     */
    protected string $connection;

    /**
     * @var array   mongodb连接配置
     */
    protected array $config;

    /**
     * @var string  表名称
     */
    protected string $table_name;

    /**
     * @var string  主键
     */
    protected string $pk = '';

    /**
     * @var array|int[] 要选择的字段
     */
    protected array $projection = ['_id' => 0];

    /**
     * @var array   查询条件
     */
    protected array $filter = [];

    protected array $operatorsArr = [
        '!=' => '$ne',
        '>' => '$gt',
        '>=' => '$gte',
        '<' => '$lt',
        '<=' => '$lte',
        'in' => '$in',
        'not' => '$not',
        'not_in' => '$nin',
        'not in' => '$nin',
    ];

    public function __construct()
    {
        $this->setConfig();
        $this->setTableName();
        $this->setCollectionObj();
    }

    /**
     * 设置mongodb连接参数
     * @author yun 2021-10-11 13:53:18
     */
    final protected function setConfig(): void
    {
        if (isset($this->config)) {
            return;
        }
        if (!isset($this->connection)) {
            $this->connection = 'default';
        }
        $configObj = ApplicationContext::getContainer()->get(ConfigInterface::class);
        $this->config = $configObj->get('mongodb.' . $this->connection);
    }

    /**
     * 设置mongodb collection 对象
     * @author yun 2021-10-11 10:42:47
     */
    final protected function setCollectionObj()
    {
        if (!isset($this->config)) {
            $this->setConfig();
        }
        $config = $this->config;
        if (!$config['username']) {
            $uri = 'mongodb://' . $config['host'] . ':' . $config['port'];
        } else {
            $uri = 'mongodb://' . $config['username'] . ':' . $config['password'] . '@' . $config['host'] . ':' . $config['port'];
        }
        $this->clientObj = new Client($uri);
        $this->collectionObj = $this->clientObj->selectCollection($this->config['database'], $this->table_name);
        $this->collectionObj->createIndexes();
    }

    /**
     * 设置表名
     * @author yun 2021-10-14 14:12:23
     */
    final protected function setTableName()
    {
        if (!isset($this->table_name)) {
            $this->table_name = Str::snake(Str::afterLast(get_class($this), '\\'));
        }
    }

    /**
     * 插入一条数据
     * @param array $data
     * @param array $options
     * @return mixed
     * @author yun 2021-10-15 11:57:49
     */
    public function insertOne(array $data, array $options = [])
    {
        return $this->execute(__FUNCTION__, $data, $options);
    }

    /**
     * 插入多条数据
     * @param array $data
     * @param array $options
     * @return mixed
     * @author yun 2021-10-15 11:57:40
     */
    public function insertMany(array $data, array $options = [])
    {
        return $this->execute(__FUNCTION__, $data, $options);
    }

    /**
     * 选择字段
     * @param array $field_arr
     * @param int $opt
     * @return Model
     */
    public function select(array $field_arr, int $opt = 1): Model
    {
        foreach ($field_arr as $field) {
            $this->projection[$field] = $opt;
        }
        return $this;
    }

    /**
     * @param $field
     * @param null $operator
     * @param null $value
     * @return Model
     * @author yun 2021-10-15 17:03:36
     */
    public function where($field, $operator = null, $value = null): Model
    {
        if (is_array($field)) {
            foreach ($field as $key => $val) {
                $this->filter[$key] = $val;
            }
            return $this;
        }
        if ($operator !== null && $value === null) {
            $this->filter[$field] = $operator;
            return $this;
        }
        if ($operator !== null && $value !== null) {
            $this->filter[$field] = [$this->operatorsArr[$operator] => $value];
        }
        return $this;
    }

    /**
     * 查询一条
     * @param null $pk_value
     * @return mixed
     */
    public function findOne($pk_value = null)
    {
        if ($pk_value !== null && $this->pk !== '') {
            return $this->execute(
                __FUNCTION__,
                [$this->pk => $pk_value],
                ['projection' => $this->projection,]
            );
        }
        return $this->execute(
            __FUNCTION__,
            $this->filter,
            [
                'projection' => $this->projection,
            ]
        );
    }

    /**
     * @Task
     * @param ...$param
     * @return mixed
     */
    final protected function execute(...$param)
    {
        $method = array_shift($param);
        return $this->collectionObj->$method(...$param)->toArray();
//        $parallel = new Parallel($this->config['concurrent']);
//        $parallel->add(function () use ($param) {
//            return $this->collectionObj->$method(...$param);
//        });
//        return $parallel->wait()[0];
    }
}