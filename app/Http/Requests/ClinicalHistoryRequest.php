<?php

namespace App\Http\Requests;

use App\Traits\HasResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ClinicalHistoryRequest extends FormRequest
{
    use HasResponse;
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'doctor_id'      => ['required', 'integer',  'validate_ids_exist:User'],
            'patient_id'     => ['required', 'integer',  'validate_ids_exist:User'],
            'reservation_id' => ['required', 'integer',  'validate_ids_exist:Reservation'],
            'tooth_model_id' => ['nullable', 'integer',  'validate_ids_exist:ToothModel'],

        ];
    }


    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->errorResponse('Formato inválido.', 422, $validator->errors()));
    }
}
