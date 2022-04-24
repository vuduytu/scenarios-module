<?php

namespace Modules\Scenarios\Repositories\ScenarioSetting;

use Modules\Scenarios\Models\ScenarioSettingModel;
use Modules\Scenarios\Repositories\_Abstract\BaseRepository;
use Modules\Scenarios\Repositories\ScenarioSetting\IScenarioSettingRepo;

class ScenarioSettingRepo extends BaseRepository implements IScenarioSettingRepo
{
    protected $model;

    public function model()
    {
        return ScenarioSettingModel::class;
    }

    public function getSelect()
    {
        return $this->select('id', 'name')->orderBy('name', 'asc')->get();
    }
}
