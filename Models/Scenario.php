<?php

namespace Modules\Scenarios\Models;

use Modules\Scenarios\Models\_Abstracts\BaseModel;
use Modules\Scenarios\Models\_Contracts\MustLogActions;
use Illuminate\Database\Eloquent\SoftDeletes;


class Scenario extends BaseModel implements MustLogActions
{
    use SoftDeletes;

    public $table = 'scenarios';

    const PRODUCTION = 1;
    const SANDBOX = 2;

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

    public $search_fields = ['displayVersionName', 'type'];

    protected $casts = [
        'specialTalks' => 'array',
    ];

}
