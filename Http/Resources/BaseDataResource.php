<?php

namespace Modules\Scenarios\Http\Resources;

use Modules\Scenarios\Http\Resources\_Abstract\JsonResourceAbstract;

class BaseDataResource extends JsonResourceAbstract
{
    public function toArray($request)
    {
        $data = parent::toArray($request);
        return $data;
    }
}
