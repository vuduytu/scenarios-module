<?php

namespace Modules\Scenarios\Http\Requests\API\Admin;

use Modules\Scenarios\Http\Requests\_Abstracts\ApiBaseRequest;
use Illuminate\Validation\Rule;

class ScenarioStoreRequest extends ApiBaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            "scenario_setting_id" => '',
            "store_id" => '',
            "displayVersionName" => [
                'required',
                Rule::unique('scenarios', 'displayVersionName')
                    ->whereNull('deleted_at')->where('store_id', auth()->user()->store_id)
            ],
            "type" => ''
        ];
    }

    public function attributes()
    {
        return ['displayVersionName' => 'バージョン'];
    }
}
