<?php

namespace Modules\Scenarios\Models;

use Modules\Scenarios\Models\_Abstracts\BaseModel;
use Modules\Scenarios\Models\_Contracts\MustLogActions;
use Illuminate\Database\Eloquent\SoftDeletes;


class ScenarioTextMappingModel extends BaseModel implements MustLogActions
{
    use SoftDeletes;

    public $table = 'scenario_textmaps';

    public $fillable = ['store_id', 'scenario_id', 'params', 'dataId', 'dataType', 'textMapping'];

    public $casts = [
        'textMapping' => 'array',
    ];

    public function scenario()
    {
        return $this->belongsTo(Scenario::class, 'scenario_id');
    }
}
