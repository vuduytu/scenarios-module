<?php

namespace Modules\Scenarios\Http\Requests\API\Admin;

use Modules\Scenarios\Http\Requests\_Abstracts\ApiBaseRequest;
use Illuminate\Validation\Rule;

class ScenarioMessageStoreRequest extends ApiBaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'scenario_id' => '',
            'scenario_talk_id' => '',
            'messages' => '',
            'dataId' => '',
            'scenario' => '',
            'talkName' => '',
            'dataType' => '',
            'nameLBD' => '',
            'params' => '',
            'talk' => '',
        ];
    }
}
