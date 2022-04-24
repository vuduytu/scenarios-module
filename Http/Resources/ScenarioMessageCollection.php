<?php

namespace Modules\Scenarios\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ScenarioMessageCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public $collects = 'Modules\Scenarios\Http\Resources\ScenarioMessageResource';

    public function toArray($request)
    {
        return $this->collection;
    }
}
