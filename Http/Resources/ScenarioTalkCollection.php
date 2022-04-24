<?php

namespace Modules\Scenarios\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ScenarioTalkCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public $collects = 'Modules\Scenarios\Http\Resources\ScenarioTalkResource';

    public function toArray($request)
    {
        return $this->collection;
    }
}
