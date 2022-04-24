<?php

namespace Modules\Scenarios\Models;

use Modules\Scenarios\Models\_Abstracts\BaseModel;
use Modules\Scenarios\Models\_Contracts\MustLogActions;
use Illuminate\Database\Eloquent\SoftDeletes;


class ScenarioUserMessageModel extends BaseModel implements MustLogActions
{
    use SoftDeletes;

    public $table = 'scenario_user_messages';

    public $fillable = ['store_id', 'scenario_id', 'params', 'type'];

    public $casts = [
        'params' => 'array',
    ];

    public function scenario()
    {
        return $this->belongsTo(Scenario::class, 'scenario_id');
    }

}
