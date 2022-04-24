<?php

namespace Modules\Scenarios\Services\Common;

use Modules\Scenarios\Repositories\_Abstract\BaseRepositoryInterface;
use Modules\Scenarios\Services\_Abstract\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;

class DataHandleService extends BaseService
{
    private $repository;

    public $collection;

    public static function initFromRepository(BaseRepositoryInterface $repository)
    {
        $obj = new self();
        $obj->repository = $repository;
        $obj->collection = $repository->getModel()->select();
        return $obj;
    }

    public function initFromCollection(?Collection $collection)
    {
        $this->collection = $collection;
        return $this;
    }

    public function search($text = null)
    {
        if (empty($text)) {
            $text = request()->get('search');
        }
        if (empty($text)) {
            return $this;
        }
        $searchFields = $this->repository->getModel()->getSearchField();
        if (!empty($searchFields)) {
            $this->collection = $this->collection->where(function ($sql) use ($searchFields, $text) {
                foreach ($searchFields as $searchField) {
                    $sql->orWhere($searchField, 'LIKE',  "%".$this->escapeLike($text)."%");
                }
            });
        }
        return $this;
    }

    public function sort($sortBy = null, $sortType = null)
    {
        if (empty($sortBy)) {
            $sortBy = request()->get('sortBy');
        }
        if (empty($sortType)) {
            $sortType = request()->get('sortType') ?? 'asc';
        }
        if (empty($sortBy)) {
            $this->collection = $this->collection->orderBy('id', 'desc');
            return $this;
        }
        $table = $this->repository->getModel()->table;
        if (Schema::hasColumn($table, $sortBy)) {
            $this->collection = $this->collection->orderBy($sortBy, $sortType);
        }
        return $this;
    }

    public function paginate($page = 1, $perPage = 10): LengthAwarePaginator
    {
        $page = request()->get('page') ?? $page;
        $perPage = request()->get('perPage') ?? $perPage;
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        return $this->collection->paginate($perPage);
    }

    public function filters($filters = [])
    {
        if (empty($filters)) {
            $searchFields = $this->repository->getModel()->getFilterFields();
            $filters = request()->get('filters') ?? [];
            foreach ($filters as $field => $filterData) {
                if (in_array($field, $searchFields)) {
                    foreach ($filterData as $type => $val) {
                        $val = trim(urldecode($val));
                        if ($type === 'equal') {
                            $this->collection = $this->collection->where($field, $val);
                        } else if ($type === 'greater_or_equal') {
                            $this->collection = $this->collection->where($field, '>=', $val);
                        } else if ($type === 'less_or_equal') {
                            $this->collection = $this->collection->where($field, '<=', $val);
                        } elseif ($type === 'like') {
                            $this->collection = $this->collection->whereRaw("$field LIKE '%" . escapeLike($val) . "%'");
                        } elseif ($type === 'not_equal') {
                            $this->collection = $this->collection->where($field, '!=', $val);
                        }
                    }
                }
            }
        }
        return $this;
    }

    public function additionQuery(\Closure $closure = null)
    {
        if ($closure) {
            $closure($this->collection);
        }
        return $this;
    }

    public function commonListQuery()
    {
        return $this->search()->filters()->sort();
    }

    public function __call($method, $args)
    {
        $initMethodName = 'init';
        if (substr($method, 0, strlen($initMethodName)) !== $initMethodName) {
            if (empty($this->collection)) {
                throw new \Exception('DataHandleService need init collection data by initFromModel() or initFromCollection() method');
            }
        }
        return call_user_func_array($method, $args);
    }
}
