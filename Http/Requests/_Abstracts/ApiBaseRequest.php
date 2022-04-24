<?php

namespace Modules\Scenarios\Http\Requests\_Abstracts;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

abstract class ApiBaseRequest extends BaseRequest
{
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $errors = $validator->errors();

        throw new HttpResponseException(response()->json([
            'result' => 'ERROR',
            'error_message' => $errors->first()
        ]));
//        $errors = (new ValidationException($validator))->errors();
//
//        throw new HttpResponseException(response()->json([
//            'errors' => $errors
//        ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY));
    }
}
