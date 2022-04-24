<?php

namespace Modules\Scenarios\Repositories\ScenarioTalk;

use Modules\Scenarios\Models\ScenarioTalkModel;
use Modules\Scenarios\Repositories\_Abstract\BaseRepository;

class ScenarioTalkRepo extends BaseRepository implements IScenarioTalkRepo
{
    protected $model;

    public function model()
    {
        return ScenarioTalkModel::class;
    }

    public function getSelect()
    {
        return $this->select('id', 'name')->orderBy('name', 'asc')->get();
    }
}
