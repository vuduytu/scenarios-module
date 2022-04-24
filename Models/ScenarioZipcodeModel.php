<?php

namespace Modules\Scenarios\Models;

use Modules\Scenarios\Models\_Abstracts\BaseModel;
use Modules\Scenarios\Models\_Contracts\MustLogActions;
use Illuminate\Database\Eloquent\SoftDeletes;


class ScenarioZipcodeModel extends BaseModel implements MustLogActions
{
    use SoftDeletes;

    public $table = 'scenario_zipcodes';

    public $fillable = ['store_id', 'scenario_id', 'scenario_talk_id', 'zipcodes', 'path'];

    public $casts = [
        'zipcodes' => 'array',
    ];

}
