<?php

namespace Modules\Scenarios\Models;

use Modules\Scenarios\Models\_Abstracts\BaseModel;
use Modules\Scenarios\Models\_Contracts\MustLogActions;
use Illuminate\Database\Eloquent\SoftDeletes;


class ScenarioModel extends BaseModel implements MustLogActions
{
    use SoftDeletes;

    public $table = 'scenarios';

    public $fillable = [
        "id",
        "store_id",
        "displayVersionName",
        "languages",
        "type",
        "scenario_setting_id",
        "created_by",
        "updated_by",
        'specialTalks',
        'disable_msg'
    ];

    protected $casts = [
        'specialTalks' => 'array',
    ];
}
