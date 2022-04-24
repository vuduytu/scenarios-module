<?php

namespace Modules\Scenarios\Http\Requests\API\Admin;

use Modules\Scenarios\Http\Requests\_Abstracts\ApiBaseRequest;
use Illuminate\Validation\Rule;

class ScenarioTextMappingUpdateRequest extends ApiBaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'textMapping' => '',
            'dataId' => '',
            'dataType' => '',
        ];
    }
}
