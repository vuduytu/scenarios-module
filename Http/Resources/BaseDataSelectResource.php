<?php

namespace Modules\Scenarios\Http\Resources;

use Modules\Scenarios\Http\Resources\_Abstract\JsonResourceAbstract;

class BaseDataSelectResource extends JsonResourceAbstract
{
    public function toArray($request)
    {
        if ($this->id) {
            $data['id'] = $this->id;
        }
        if ($this->name) {
            $data['name'] = $this->name;
        }
        return $data;
    }
}
