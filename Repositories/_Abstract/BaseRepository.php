<?php

namespace Modules\Scenarios\Repositories\_Abstract;

use Prettus\Repository\Eloquent\BaseRepository as BRepository;

/**
 * Class BaseRepository
 *
 * @package App\Entities\Admin\Repositories
 */
abstract class BaseRepository extends BRepository
{
    public function find($id, $columns = ['*'])
    {
        $this->applyCriteria();
        $this->applyScope();
        $model = $this->model->lockForUpdate()->findOrFail($id, $columns);
        $this->resetModel();

        return $this->parserResult($model);
    }

    public function getSelect()
    {
        return $this->select('id', 'name')->orderBy('id', 'desc')->get();
    }
}
