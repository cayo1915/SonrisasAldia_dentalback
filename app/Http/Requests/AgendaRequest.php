<?php

namespace App\Http\Requests;

use App\Traits\HasResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AgendaRequest extends FormRequest
{
    use HasResponse;
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'doctor_ids'            => ['required', 'array', 'min:1'],
            'doctor_ids.*'          => ['integer', 'validate_ids_exist:User'],
            'modalityIds'           => ['nullable', 'string'],
            'name'                  => ['required', 'string', 'max:150'],
            'days_array'            => ['required', 'array', 'min:1'],
            'days_array.*.day'      => ['required', 'integer', 'between:1,7'],
            'days_array.*.start'    => ['required', 'date_format:H:i'],
            'days_array.*.end'      => ['required', 'date_format:H:i'],
            'duration_hour'         => ['required', 'integer'],
            'wait_time_hour'        => ['required', 'integer'],
            'break_status'          => ['required', 'boolean'],
            'break_start'           => ['required_if:break_status,true', 'date_format:H:i'],
            'break_end'             => ['required_if:break_status,true', 'date_format:H:i'],
            'comment'               => ['nullable', 'string', 'max:255'],
            'preview'               => ['required', 'boolean'],
            'removehorary'          => ['nullable', 'array'],
        ];
    }
    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->errorResponse('Formato inválido.', 422, $validator->errors()));
    }
}
