<?php

namespace Modules\Scenarios\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ScenarioUserMessageDataCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public $collects = 'Modules\Scenarios\Http\Resources\ScenarioUserMessageDataTypeResource';

    public function toArray($request)
    {
        return $this->collection;
    }
}
