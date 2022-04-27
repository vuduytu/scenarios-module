<?php

namespace Modules\Scenarios\Models;

use Modules\Scenarios\Models\_Abstracts\BaseModel;
use Modules\Scenarios\Models\_Contracts\MustLogActions;
use Illuminate\Database\Eloquent\SoftDeletes;


class ScenarioMessageModel extends BaseModel implements MustLogActions
{
    use SoftDeletes;

    const TALK_FLOW_MAP = [
        "損傷報告" => "REPORT_MODE_BUTTON",
        "防災" => "BOSAI_DISASTER_SELECT",
        "防災検索" => "NORMAL_SHELTER_SEARCH",
        "防災（大雨・台風）" => "RAIN_TYPHOON_SEARCH_START",
        "防災（地震）" => "EARTHQUAKE_SEARCH_START"
    ];

    const SIMULATE_RESPONSE = [
        "NORMAL_SHELTER_SEARCH" => [
            ['NORMAL_SHELTER_SEARCH_RESULT', 'DUMMY_BOSAI_SHELTER_CAROUSEL', 'NORMAL_GUIDANCE_ENDED'],
            ['NORMAL_SHELTER_NOT_FOUND']
        ],
        "NORMAL_SHELTER_NOT_FOUND" => [
            ['NORMAL_SHELTER_SEARCH_RESULT', 'DUMMY_BOSAI_SHELTER_CAROUSEL', 'NORMAL_GUIDANCE_ENDED'],
            ['NORMAL_SHELTER_NOT_FOUND']
        ],
        "CATEGORY_NORMAL_BUTTON" => [
            ['CATEGORY_DETAILS_PARK_CAROUSEL'],
            ['']
        ],
        "CAMERA_ACTION_DETAILED_PICTURE_BUTTON" => [
            ['CAMERA_DETAILED_PICTURE_CONFIRM'],
            ['']
        ],
        "CATEGORY_DETAILS_PARK_CAROUSEL" => [
            ['CAMERA_ACTION_DETAILED_PICTURE_BUTTON'],
            ['']
        ],
        "CAMERA_DETAILED_PICTURE_CONFIRM" => [
            ['STATUS_USER_COMMENT_RIVER_BUTTON', 'STATUS_USER_COMMENT_PARK_PLAYGROUND_BUTTON', 'STATUS_USER_COMMENT_PARK_LIGHTING_BUTTON',
                'STATUS_USER_COMMENT_PARK_BENCH_BUTTON', 'STATUS_USER_COMMENT_PARK_WATER_BUTTON', 'STATUS_USER_COMMENT_PARK_TREE_BUTTON', 'STATUS_USER_COMMENT_PARK_OTHER_BUTTON'],
            ['']
        ],
        "STATUS_USER_COMMENT_RIVER_BUTTON" => [
            ['STATUS_USER_COMMENT_CONFIRM'],
            ['']
        ],
        "STATUS_USER_COMMENT_PARK_PLAYGROUND_BUTTON" => [
            ['STATUS_USER_COMMENT_CONFIRM'],
            ['']
        ],
        "STATUS_USER_COMMENT_PARK_BENCH_BUTTON" => [
            ['STATUS_USER_COMMENT_CONFIRM'],
            ['']
        ],
        "STATUS_USER_COMMENT_PARK_WATER_BUTTON" => [
            ['STATUS_USER_COMMENT_CONFIRM'],
            ['']
        ],
        "STATUS_USER_COMMENT_PARK_TREE_BUTTON" => [
            ['STATUS_USER_COMMENT_CONFIRM'],
            ['']
        ],
        "LOCATION_PICKER_BUTTON" => [
            ['LOCATION_OUTSIDE_SEARCH_RADIUS', 'LOCATION_CONFIRM'],
            ['']
        ],
        "CAMERA_ACTION_BUTTON" => [
            ['CAMERA_PICTURE_CONFIRM'],
            ['']
        ],
        "REPORT_RESUME_CONFIRM" => [
            [''],
            ['']
        ],
        "BOSAI_SHELTER_SEARCH_CONFIRM_1_1" => [
            ['BOSAI_SHELTER_SEARCH_RESULT', 'DUMMY_BOSAI_SHELTER_CAROUSEL', 'BOSAI_GUIDANCE_ENDED'],
            ['BOSAI_SHELTER_NOT_FOUND']
        ],
        "BOSAI_SHELTER_SEARCH_CONFIRM_1_2" => [
            ['BOSAI_SHELTER_SEARCH_RESULT', 'DUMMY_BOSAI_SHELTER_CAROUSEL', 'BOSAI_GUIDANCE_ENDED'],
            ['BOSAI_SHELTER_NOT_FOUND']
        ],
        "BOSAI_SHELTER_SEARCH_CONFIRM_1_3" => [
            ['BOSAI_SHELTER_SEARCH_RESULT', 'DUMMY_BOSAI_SHELTER_CAROUSEL', 'BOSAI_GUIDANCE_ENDED'],
            ['BOSAI_SHELTER_NOT_FOUND']
        ],
        "BOSAI_SHELTER_SEARCH_CONFIRM_1_4" => [
            ['BOSAI_SHELTER_SEARCH_RESULT', 'DUMMY_BOSAI_SHELTER_CAROUSEL', 'BOSAI_GUIDANCE_ENDED'],
            ['BOSAI_SHELTER_NOT_FOUND']
        ],
        "BOSAI_SHELTER_SEARCH_CONFIRM_2_1" => [
            ['BOSAI_SHELTER_SEARCH_RESULT', 'DUMMY_BOSAI_SHELTER_CAROUSEL', 'BOSAI_GUIDANCE_ENDED'],
            ['BOSAI_SHELTER_NOT_FOUND']
        ],
        "BOSAI_SHELTER_SEARCH_CONFIRM_2_2" => [
            ['BOSAI_SHELTER_SEARCH_RESULT', 'DUMMY_BOSAI_SHELTER_CAROUSEL', 'BOSAI_GUIDANCE_ENDED'],
            ['BOSAI_SHELTER_NOT_FOUND']
        ],
        "BOSAI_SHELTER_SEARCH_CONFIRM_2_3" => [
            ['BOSAI_SHELTER_SEARCH_RESULT', 'DUMMY_BOSAI_SHELTER_CAROUSEL', 'BOSAI_GUIDANCE_ENDED'],
            ['BOSAI_SHELTER_NOT_FOUND']
        ],
        "BOSAI_SHELTER_SEARCH_CONFIRM_2_4" => [
            ['BOSAI_SHELTER_SEARCH_RESULT', 'DUMMY_BOSAI_SHELTER_CAROUSEL', 'BOSAI_GUIDANCE_ENDED'],
            ['BOSAI_SHELTER_NOT_FOUND']
        ],
        "BOSAI_SHELTER_SEARCH_CONFIRM_3_1" => [
            ['BOSAI_SHELTER_SEARCH_RESULT', 'DUMMY_BOSAI_SHELTER_CAROUSEL', 'BOSAI_GUIDANCE_ENDED'],
            ['BOSAI_SHELTER_NOT_FOUND']
        ],
        "BOSAI_SHELTER_SEARCH_CONFIRM_3_2" => [
            ['BOSAI_SHELTER_SEARCH_RESULT', 'DUMMY_BOSAI_SHELTER_CAROUSEL', 'BOSAI_GUIDANCE_ENDED'],
            ['BOSAI_SHELTER_NOT_FOUND']
        ],
        "BOSAI_SHELTER_SEARCH_CONFIRM_3_3" => [
            ['BOSAI_SHELTER_SEARCH_RESULT', 'DUMMY_BOSAI_SHELTER_CAROUSEL', 'BOSAI_GUIDANCE_ENDED'],
            ['BOSAI_SHELTER_NOT_FOUND']
        ],
        "BOSAI_SHELTER_SEARCH_CONFIRM_3_4" => [
            ['BOSAI_SHELTER_SEARCH_RESULT', 'DUMMY_BOSAI_SHELTER_CAROUSEL', 'BOSAI_GUIDANCE_ENDED'],
            ['BOSAI_SHELTER_NOT_FOUND']
        ],
        "BOSAI_SHELTER_SEARCH_CONFIRM_4_1" => [
            ['BOSAI_SHELTER_SEARCH_RESULT', 'DUMMY_BOSAI_SHELTER_CAROUSEL', 'BOSAI_GUIDANCE_ENDED'],
            ['BOSAI_SHELTER_NOT_FOUND']
        ],
        "BOSAI_SHELTER_SEARCH_CONFIRM_4_2" => [
            ['BOSAI_SHELTER_SEARCH_RESULT', 'DUMMY_BOSAI_SHELTER_CAROUSEL', 'BOSAI_GUIDANCE_ENDED'],
            ['BOSAI_SHELTER_NOT_FOUND']
        ],
        "BOSAI_SHELTER_SEARCH_CONFIRM_4_3" => [
            ['BOSAI_SHELTER_SEARCH_RESULT', 'DUMMY_BOSAI_SHELTER_CAROUSEL', 'BOSAI_GUIDANCE_ENDED'],
            ['BOSAI_SHELTER_NOT_FOUND']
        ],
        "BOSAI_SHELTER_SEARCH_CONFIRM_4_4" => [
            ['BOSAI_SHELTER_SEARCH_RESULT', 'DUMMY_BOSAI_SHELTER_CAROUSEL', 'BOSAI_GUIDANCE_ENDED'],
            ['BOSAI_SHELTER_NOT_FOUND']
        ],
        "BOSAI_SHELTER_NOT_FOUND" => [
            ['BOSAI_SHELTER_SEARCH_RESULT', 'DUMMY_BOSAI_SHELTER_CAROUSEL', 'BOSAI_GUIDANCE_ENDED'],
            ['BOSAI_SHELTER_NOT_FOUND']
        ],
        "RAIN_TYPHOON_IN_RISK_AREA" => [
            ['RAIN_TYPHOON_SHELTER_SEARCH_RESULT', 'DUMMY_BOSAI_SHELTER_CAROUSEL', 'RAIN_TYPHOON_GUIDANCE_ENDED'],
            ['RAIN_TYPHOON_SHELTER_NOT_FOUND']
        ],
        "RAIN_TYPHOON_OUTSIDE_RISK_AREA_1" => [
            ['RAIN_TYPHOON_SHELTER_SEARCH_RESULT', 'DUMMY_BOSAI_SHELTER_CAROUSEL', 'RAIN_TYPHOON_GUIDANCE_ENDED'],
            ['RAIN_TYPHOON_SHELTER_NOT_FOUND']
        ],
        "RAIN_TYPHOON_OUTSIDE_RISK_AREA_2" => [
            ['RAIN_TYPHOON_SHELTER_SEARCH_RESULT', 'DUMMY_BOSAI_SHELTER_CAROUSEL', 'RAIN_TYPHOON_GUIDANCE_ENDED'],
            ['RAIN_TYPHOON_SHELTER_NOT_FOUND']
        ],
        "RAIN_TYPHOON_SHELTER_NOT_FOUND" => [
            ['RAIN_TYPHOON_SHELTER_SEARCH_RESULT', 'DUMMY_BOSAI_SHELTER_CAROUSEL', 'RAIN_TYPHOON_GUIDANCE_ENDED'],
            ['RAIN_TYPHOON_SHELTER_NOT_FOUND']
        ],
        "EARTHQUAKE_WHEREABOUTS_1_AREA_1" => [
            ['EARTHQUAKE_SHELTER_SEARCH_RESULT', 'DUMMY_BOSAI_SHELTER_CAROUSEL', 'EARTHQUAKE_GUIDANCE_ENDED'],
            ['EARTHQUAKE_SHELTER_NOT_FOUND']
        ],
        "EARTHQUAKE_WHEREABOUTS_1_AREA_2" => [
            ['EARTHQUAKE_SHELTER_SEARCH_RESULT', 'DUMMY_BOSAI_SHELTER_CAROUSEL', 'EARTHQUAKE_GUIDANCE_ENDED'],
            ['EARTHQUAKE_SHELTER_NOT_FOUND']
        ],
        "EARTHQUAKE_WHEREABOUTS_1_AREA_3" => [
            ['EARTHQUAKE_SHELTER_SEARCH_RESULT', 'DUMMY_BOSAI_SHELTER_CAROUSEL', 'EARTHQUAKE_GUIDANCE_ENDED'],
            ['EARTHQUAKE_SHELTER_NOT_FOUND']
        ],
        "EARTHQUAKE_WHEREABOUTS_1_AREA_4" => [
            ['EARTHQUAKE_SHELTER_SEARCH_RESULT', 'DUMMY_BOSAI_SHELTER_CAROUSEL', 'EARTHQUAKE_GUIDANCE_ENDED'],
            ['EARTHQUAKE_SHELTER_NOT_FOUND']
        ],
        "EARTHQUAKE_WHEREABOUTS_1_AREA_5" => [
            ['EARTHQUAKE_SHELTER_SEARCH_RESULT', 'DUMMY_BOSAI_SHELTER_CAROUSEL', 'EARTHQUAKE_GUIDANCE_ENDED'],
            ['EARTHQUAKE_SHELTER_NOT_FOUND']
        ],
        "EARTHQUAKE_WHEREABOUTS_1_AREA_6" => [
            ['EARTHQUAKE_SHELTER_SEARCH_RESULT', 'DUMMY_BOSAI_SHELTER_CAROUSEL', 'EARTHQUAKE_GUIDANCE_ENDED'],
            ['EARTHQUAKE_SHELTER_NOT_FOUND']
        ],
        "EARTHQUAKE_WHEREABOUTS_1_AREA_7" => [
            ['EARTHQUAKE_SHELTER_SEARCH_RESULT', 'DUMMY_BOSAI_SHELTER_CAROUSEL', 'EARTHQUAKE_GUIDANCE_ENDED'],
            ['EARTHQUAKE_SHELTER_NOT_FOUND']
        ],
        "EARTHQUAKE_WHEREABOUTS_2_AREA_1" => [
            ['EARTHQUAKE_SHELTER_SEARCH_RESULT', 'DUMMY_BOSAI_SHELTER_CAROUSEL', 'EARTHQUAKE_GUIDANCE_ENDED'],
            ['EARTHQUAKE_SHELTER_NOT_FOUND']
        ],
        "EARTHQUAKE_WHEREABOUTS_2_AREA_2" => [
            ['EARTHQUAKE_SHELTER_SEARCH_RESULT', 'DUMMY_BOSAI_SHELTER_CAROUSEL', 'EARTHQUAKE_GUIDANCE_ENDED'],
            ['EARTHQUAKE_SHELTER_NOT_FOUND']
        ],
        "EARTHQUAKE_WHEREABOUTS_2_AREA_3" => [
            ['EARTHQUAKE_SHELTER_SEARCH_RESULT', 'DUMMY_BOSAI_SHELTER_CAROUSEL', 'EARTHQUAKE_GUIDANCE_ENDED'],
            ['EARTHQUAKE_SHELTER_NOT_FOUND']
        ],
        "EARTHQUAKE_WHEREABOUTS_2_AREA_4" => [
            ['EARTHQUAKE_SHELTER_SEARCH_RESULT', 'DUMMY_BOSAI_SHELTER_CAROUSEL', 'EARTHQUAKE_GUIDANCE_ENDED'],
            ['EARTHQUAKE_SHELTER_NOT_FOUND']
        ],
        "EARTHQUAKE_WHEREABOUTS_2_AREA_5" => [
            ['EARTHQUAKE_SHELTER_SEARCH_RESULT', 'DUMMY_BOSAI_SHELTER_CAROUSEL', 'EARTHQUAKE_GUIDANCE_ENDED'],
            ['EARTHQUAKE_SHELTER_NOT_FOUND']
        ],
        "EARTHQUAKE_WHEREABOUTS_3_AREA_1" => [
            ['EARTHQUAKE_SHELTER_SEARCH_RESULT', 'DUMMY_BOSAI_SHELTER_CAROUSEL', 'EARTHQUAKE_GUIDANCE_ENDED'],
            ['EARTHQUAKE_SHELTER_NOT_FOUND']
        ],
        "EARTHQUAKE_WHEREABOUTS_3_AREA_2" => [
            ['EARTHQUAKE_SHELTER_SEARCH_RESULT', 'DUMMY_BOSAI_SHELTER_CAROUSEL', 'EARTHQUAKE_GUIDANCE_ENDED'],
            ['EARTHQUAKE_SHELTER_NOT_FOUND']
        ],
        "EARTHQUAKE_WHEREABOUTS_3_AREA_3" => [
            ['EARTHQUAKE_SHELTER_SEARCH_RESULT', 'DUMMY_BOSAI_SHELTER_CAROUSEL', 'EARTHQUAKE_GUIDANCE_ENDED'],
            ['EARTHQUAKE_SHELTER_NOT_FOUND']
        ],
        "EARTHQUAKE_SHELTER_NOT_FOUND" => [
            ['EARTHQUAKE_SHELTER_SEARCH_RESULT', 'DUMMY_BOSAI_SHELTER_CAROUSEL', 'EARTHQUAKE_GUIDANCE_ENDED'],
            ['EARTHQUAKE_SHELTER_NOT_FOUND']
        ]
    ];
    const SIMULATE_RESPONSE_DATA = [
        "公園" => [
            ['CATEGORY_DETAILS_PARK_CAROUSEL'],
            ['']
        ],
        "河川" => [
            ['CAMERA_ACTION_DETAILED_PICTURE_BUTTON'],
            ['']
        ],
        "照明灯" => [
            ['CAMERA_ACTION_DETAILED_PICTURE_BUTTON'],
            ['']
        ],
        "ベンチなど（休憩施設）" => [
            ['CAMERA_ACTION_DETAILED_PICTURE_BUTTON'],
            ['']
        ],
        "水回り（水道，トイレなど）" => [
            ['CAMERA_ACTION_DETAILED_PICTURE_BUTTON'],
            ['']
        ],
        "樹木" => [
            ['CAMERA_ACTION_DETAILED_PICTURE_BUTTON'],
            ['']
        ],
        "その他" => [
            ['CAMERA_ACTION_DETAILED_PICTURE_BUTTON'],
            ['']
        ],
        "はい" => [
            ['STATUS_USER_COMMENT_RIVER_BUTTON', 'STATUS_USER_COMMENT_PARK_PLAYGROUND_BUTTON', 'STATUS_USER_COMMENT_PARK_LIGHTING_BUTTON',
                'STATUS_USER_COMMENT_PARK_BENCH_BUTTON', 'STATUS_USER_COMMENT_PARK_WATER_BUTTON', 'STATUS_USER_COMMENT_PARK_TREE_BUTTON', 'STATUS_USER_COMMENT_PARK_OTHER_BUTTON'],
            ['']
        ],
        "破損" => [
            ['STATUS_USER_COMMENT_CONFIRM'],
            ['']
        ],
        "木や草が茂っている" => [
            ['STATUS_USER_COMMENT_CONFIRM'],
            ['']
        ],
        "油・泡が浮いている" => [
            ['STATUS_USER_COMMENT_CONFIRM'],
            ['']
        ],
        "不点灯" => [
            ['STATUS_USER_COMMENT_CONFIRM'],
            ['']
        ],
        "その他（自由記入）" => [
            ['STATUS_USER_COMMENT_FREEFORM_INPUT'],
            ['']
        ],
        "水漏れ" => [
            ['STATUS_USER_COMMENT_CONFIRM'],
            ['']
        ],
        "水が出ない・流れない" => [
            ['STATUS_USER_COMMENT_CONFIRM'],
            ['']
        ],
        "枝折れ" => [
            ['STATUS_USER_COMMENT_CONFIRM'],
            ['']
        ],
        "倒木" => [
            ['STATUS_USER_COMMENT_CONFIRM'],
            ['']
        ],
        "害虫発生" => [
            ['STATUS_USER_COMMENT_CONFIRM'],
            ['']
        ],
        "スキップする" => [
            ['REPORT_RESUME_CONFIRM'],
            ['']
        ],
        "送る" => [
            ['END_REPORT_MODE_COMPOSITE_MESSAGE'],
            ['']
        ],
        "やめる" => [
            [''],
            ['']
        ],
        "遊具" => [
            ['CAMERA_ACTION_DETAILED_PICTURE_BUTTON'],
            ['']
        ]
    ];

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
