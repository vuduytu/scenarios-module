<?php

namespace Modules\Scenarios\Services;

use Modules\Scenarios\Repositories\ScenarioUserMessage\IScenarioUserMessageRepo;

class ScenarioUserMessageService
{
    protected $mainRepository;

    public function __construct(IScenarioUserMessageRepo $entryRepo)
    {
        $this->mainRepository = $entryRepo;
    }
}
