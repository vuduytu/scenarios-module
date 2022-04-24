<?php

namespace Modules\Scenarios\Http\Requests\_Abstracts;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    abstract public function rules();

    public function authorize()
    {
        return true;
    }

    public function fillData()
    {
        return $this->only(array_keys($this->rules()));
    }
}
