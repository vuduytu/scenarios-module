<?php

namespace Modules\Scenarios\Models;

use Modules\Scenarios\Models\_Abstracts\BaseModel;
use Modules\Scenarios\Models\_Contracts\MustLogActions;
use Illuminate\Database\Eloquent\SoftDeletes;


class ScenarioMessageModel extends BaseModel implements MustLogActions
{
    use SoftDeletes;

    public $table = 'scenario_messages';

    public $fillable = ['store_id', 'scenario_id', 'scenario_talk_id', 'dataId', 'dataType', 'params', 'nameLBD', 'originalLBD',
        'userInput', 'previewIcon', 'previewType', 'previewValue', 'talk', 'expandNote', 'newMessage', 'messages', 'is_extension',
        'generation'];

    public $casts = [
        'params' => 'array',
        'originalLBD' => 'array',
        'userInput' => 'array',
        'messages' => 'array',
    ];

    public function talkModel()
    {
        return $this->belongsTo(ScenarioTalkModel::class, 'scenario_talk_id');
    }

}
