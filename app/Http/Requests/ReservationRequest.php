<?php

namespace App\Http\Requests;

use App\Enums\TypeModality;
use App\Traits\HasResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ReservationRequest extends FormRequest
{
    use HasResponse;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date'          => ['required', 'date'],
            'idhorary'      => ['required', 'integer'],
            'idpatient'     => ['required', 'integer'],
            'type_modality' => [
                'sometimes',
                'string',
                Rule::in([
                    TypeModality::IN_PERSON->value,
                    TypeModality::VIRTUAL->value,
                    TypeModality::BOTH->value
                ])
            ],
            'description' => ['nullable', 'string', 'max:250']
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->errorResponse('Formato inválido.', 422, $validator->errors()));
    }
}
