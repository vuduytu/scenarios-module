<?php

namespace Modules\Scenarios\Http\Resources;

use Modules\Scenarios\Models\ScenarioTalkModel;

class ScenarioTalkResource extends BaseDataResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $this->load('messages');
        $data = parent::toArray($request);
        $data['_name'] = $data['params']['name'];
        $data['_messageCount'] = count($data['messages']);
        if ($data['numberOfMessage'] == null && $data['numberOfMessage'] != $data['_messageCount']) {
            $this->update(['numberOfMessage' => $data['_messageCount']]);
        }
        $data['_editButton'] = null;
        $data['_startMessage'] = $data['startMessage'] != '' ? $data['startMessage'] : '-';
        return $data;
    }
}
