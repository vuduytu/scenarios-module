<?php

namespace Modules\Scenarios\Http\Resources;

use Modules\Scenarios\Models\ScenarioTalkModel;

class ScenarioTextMappingDataTypeResource extends BaseDataResource
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
        $result['textMapping'] = json_decode(json_encode($data['textMapping']));
        $result['dataId'] = $data['dataId'] ? $data['dataId'] : $data['id'];
        $result['dataType'] = $data['dataType'];
        $result['scenario'] = $request->get('scenarioId'). '#' . @$data['scenario']['id'];
        return $result;
    }
}
