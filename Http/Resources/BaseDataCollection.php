<?php

namespace Modules\Scenarios\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class BaseDataCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return parent::toArray($request);
    }
}
