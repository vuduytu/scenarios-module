<?php

namespace Modules\Scenarios\Http\Requests\API\Admin;

use Modules\Scenarios\Http\Requests\_Abstracts\ApiBaseRequest;
use Illuminate\Validation\Rule;

class ScenarioSettingUpdateRequest extends ApiBaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            "store_id" => '',
            "name" => 'required',
            "richMenu" => '',
            "envMapping" => ''
        ];
    }
}
