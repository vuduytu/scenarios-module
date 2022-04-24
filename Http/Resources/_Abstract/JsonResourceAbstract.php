<?php

namespace Modules\Scenarios\Http\Resources\_Abstract;

use Illuminate\Http\Resources\Json\JsonResource;

abstract class JsonResourceAbstract extends JsonResource
{
    public function toArray($request)
    {
        $data = parent::toArray($request);
//        if ($this->created_at) {
//            $data['created_at'] = convert_date($this->created_at);
//        }
//        if ($this->updated_at) {
//            $data['updated_at'] = $this->updated_at == $this->created_at ? '' : convert_date($this->updated_at);
//        }

        return $data;
    }
}
