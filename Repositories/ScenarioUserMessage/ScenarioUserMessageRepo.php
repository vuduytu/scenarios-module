<?php

namespace Modules\Scenarios\Repositories\ScenarioUserMessage;

use Modules\Scenarios\Models\ScenarioUserMessageModel;
use Modules\Scenarios\Repositories\_Abstract\BaseRepository;

class ScenarioUserMessageRepo extends BaseRepository implements IScenarioUserMessageRepo
{
    protected $model;

    public function model()
    {
        return ScenarioUserMessageModel::class;
    }

    public function getSelect()
    {
        return $this->select('id', 'name')->orderBy('name', 'asc')->get();
    }
}
