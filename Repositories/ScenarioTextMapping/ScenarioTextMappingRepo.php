<?php

namespace Modules\Scenarios\Repositories\ScenarioTextMapping;

use Modules\Scenarios\Models\ScenarioTextMappingModel;
use Modules\Scenarios\Repositories\_Abstract\BaseRepository;

class ScenarioTextMappingRepo extends BaseRepository implements IScenarioTextMappingRepo
{
    protected $model;

    public function model()
    {
        return ScenarioTextMappingModel::class;
    }

    public function getSelect()
    {
        return $this->select('id', 'name')->orderBy('name', 'asc')->get();
    }
}
