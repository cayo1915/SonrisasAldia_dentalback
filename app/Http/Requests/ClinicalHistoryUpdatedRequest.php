<?php

namespace App\Http\Requests;

use App\Traits\HasResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ClinicalHistoryUpdatedRequest extends FormRequest
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
            'register_date'     => ['nullable', 'date'],
            'history_number'    => ['nullable', 'string'],
            'document_number'   => ['nullable', 'string'],
            'medical_condition' => ['nullable', 'string'],
            'allergies'         => ['nullable', 'string'],
            'observation'       => ['nullable', 'string'],

            'n_document'        => ['required', 'string', 'max:15'],
            'name'              => ['required', 'string', 'max:45'],
            'surname'           => ['required', 'string', 'max:45'],
            'birth_date'        => ['nullable', 'date', 'before:today'],
            'phone'             => ['nullable', 'string', 'max:15'],
        ];
    }


    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->errorResponse('Formato inválido.', 422, $validator->errors()));
    }
}
