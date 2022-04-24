<?php

namespace Modules\Scenarios\Models;

use Modules\Scenarios\Models\_Abstracts\BaseModel;
use Modules\Scenarios\Models\_Contracts\MustLogActions;
use Illuminate\Database\Eloquent\SoftDeletes;


class ScenarioSettingModel extends BaseModel implements MustLogActions
{
    use SoftDeletes;

    public $table = 'scenario_settings';

    public $fillable = [
        "id",
        "store_id",
        "name",
        "richMenu",
        "envMapping",
        "type",
        "created_by",
        "updated_by"
    ];

    public function scenarios()
    {
        return $this->hasMany(Scenario::class, 'scenario_setting_id');
    }
}
