<?php

namespace Modules\Scenarios\Repositories\Scenario;

use Modules\Scenarios\Models\Scenario;
use Modules\Scenarios\Repositories\_Abstract\BaseRepository;
use Modules\Scenarios\Repositories\Scenario\IScenarioRepo;

class ScenarioRepo extends BaseRepository implements IScenarioRepo
{
    protected $model;

    public function model()
    {
        return Scenario::class;
    }

    public function getSelect()
    {
        return $this->select('id', 'name')->orderBy('name', 'asc')->get();
    }
}
