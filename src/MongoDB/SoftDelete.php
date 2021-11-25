<?php

namespace Jiajushe\HyperfHelper\MongoDB;

trait SoftDelete
{
    public string $deleted_at = 'deleted_at';

    protected bool $only_trashed = false;
    protected bool $with_trashed = false;

    public function insertHandle(array $document): array
    {
        foreach ($document as $index => $item) {
            $item = $this->changeObjectId($item);
            $item = $this->setAttr($item);
            $item[$this->deleted_at] = 0;
            $document[$index] = $item;
        }
        $document = $this->addTime($document);
        return $document;
    }

    public function upsertDefaultHandle(array $default): array
    {
        $default[$this->created_at] = time();
        $default[$this->deleted_at] = 0;
        $default = $this->changeObjectId($default);
        return $default;
    }

    public function getFilter(): array
    {
        $filter = $this->filter;
        if (empty($filter['$and'])) {
            $filter = [];
        }
        if ($this->only_trashed && !$this->with_trashed) {
            $filter['$and'][] = [$this->deleted_at => ['$gt' => 0]];
        }
        if (!$this->only_trashed && !$this->with_trashed) {
            $filter['$and'][] = ['$or' => [[$this->deleted_at => 0], [$this->deleted_at => ['$exists' => false]]]];
        }
        return $filter;
    }

    public function resetFilter(): Model
    {
        $this->filter = ['$and' => []];
        $this->only_trashed = false;
        $this->with_trashed = false;
        return $this;
    }

    public function delete(string $id = null, int $timeout = 10000): array
    {
        if ($id) {
            $this->where('id', '=', $id);
        }
        $document = [$this->deleted_at => time()];
        $res = $this->getModelTask()->update($this->config, $this->getFilter(), $document, $timeout);
        $this->resetOptions();
        $this->resetFilter();
        $this->resetPipeline();
        return $res;
    }

    /**
     * 永久删除
     * @param string|null $id
     * @param int $timeout
     * @return array
     */
    public function forceDelete(string $id = null, int $timeout = 10000): array
    {
        if ($id) {
            $this->where('id', '=', $id);
        }
        $res = $this->getModelTask()->delete($this->config, $this->getFilter(), $timeout);
        $this->resetOptions();
        $this->resetFilter();
        $this->resetPipeline();
        return $res;
    }


    public function getOptions(): array
    {
        $this->select([$this->deleted_at], false);
        return $this->options;
    }

    public function onlyTrashed(): Model
    {
        $this->only_trashed = true;
        return $this;
    }

    public function withTrashed(): Model
    {
        $this->with_trashed = true;
        return $this;
    }
}