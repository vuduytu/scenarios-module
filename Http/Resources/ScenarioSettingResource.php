<?php

namespace Modules\Scenarios\Http\Resources;

use Modules\Scenarios\Models\Scenario;

class ScenarioSettingResource extends BaseDataResource
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
        $entries = Scenario::where('scenario_setting_id', $data['id'])->get();
        $versions = [];
        foreach ($entries as $key => $value) {
            $value->specialTalks = is_array($value->specialTalks) ? $value->specialTalks : [];
            $versions[$value->id] = $value;
        }
        $mapping = json_decode($data['envMapping'], true);
        $env = [
            'production' => @$mapping['production'],
            'sandbox' => @$mapping['sandbox']
        ];

        $data['activeScenarioId'] = $data['name'];
        $data['scenarioId'] = $data['id'];
        $data['versions'] = $versions;
        $data['envMapping'] = $env;
        return $data;
    }
}
