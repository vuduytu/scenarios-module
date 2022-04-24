<?php

namespace Modules\Scenarios\Repositories\ScenarioMessage;

use Modules\Scenarios\Models\ScenarioMessageModel;
use Modules\Scenarios\Repositories\_Abstract\BaseRepository;

class ScenarioMessageRepo extends BaseRepository implements IScenarioMessageRepo
{
    protected $model;

    public function model()
    {
        return ScenarioMessageModel::class;
    }

    public function getSelect()
    {
        return $this->select('id', 'name')->orderBy('name', 'asc')->get();
    }
}
