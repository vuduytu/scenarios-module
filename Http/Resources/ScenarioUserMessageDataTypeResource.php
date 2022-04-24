<?php

namespace Modules\Scenarios\Http\Resources;

use Modules\Scenarios\Models\ScenarioTalkModel;

class ScenarioUserMessageDataTypeResource extends BaseDataResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $data = parent::toArray($request);
        $result = [];
        $result['params'] = $data['params'];
        $result['dataId'] = 'userMessage';
        $result['dataType'] = 'userMessage';
        $result['scenario'] = @$data['scenario']['displayVersionName'];
        return $result;
    }
}
