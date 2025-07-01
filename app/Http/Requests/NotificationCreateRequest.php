<?php

namespace App\Http\Requests;

use App\Traits\HasResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;


class NotificationCreateRequest extends FormRequest
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
            'ids_receiver'      => ['required', 'array', 'min:1'],
            'ids_receiver.*'    => ['integer', 'validate_ids_exist:User'], // validar que cada id exista en users
            'message_title'     => ['required', 'string', 'max:255'],
            'message_body'      => ['required', 'string'],
            'data_json'         => ['nullable', 'array'],
            'idsender'          => ['nullable', 'integer', 'validate_ids_exist:User'],
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->errorResponse('Formato inválido.', 422, $validator->errors()));
    }
}
