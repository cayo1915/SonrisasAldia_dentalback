<?php

namespace App\Http\Requests;

use App\Traits\HasResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AgendaUpdateRequest extends FormRequest
{
    use HasResponse;

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'doctor_id'             => ['required', 'integer',  'validate_ids_exist:User'],
            'days_array'            => ['required', 'array', 'min:1'],
            'days_array.*.day'      => ['required', 'integer', 'between:1,7'],
            'days_array.*.start'    => ['required', 'date_format:H:i'],
            'days_array.*.end'      => ['required', 'date_format:H:i'],
            'duration_hour'         => ['required', 'integer'],
            'wait_time_hour'        => ['required', 'integer'],
            'break_status'          => ['required', 'boolean'],
            'break_start'           => ['required_if:break_status,true', 'date_format:H:i'],
            'break_end'             => ['required_if:break_status,true', 'date_format:H:i'],
            'preview'               => ['required', 'boolean'],
            'name'                  => ['required', 'string'],
            'modalityIds'           => ['required', 'string'],
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->errorResponse('Formato inválido.', 422, $validator->errors()));
    }
}
