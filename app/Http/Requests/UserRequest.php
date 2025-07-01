<?php

namespace App\Http\Requests;

use App\Traits\HasResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserRequest extends FormRequest
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
        $rules = [
            'n_document'  => ['required', 'string', 'max:15'],
            'name'        => ['required', 'string', 'max:45'],
            'surname'     => ['required', 'string', 'max:45'],
            'birth_date'  => ['nullable', 'date', 'before:today'],
            'phone'       => ['nullable', 'string', 'max:15'],
            'email'       => ['required', 'email', 'max:150'],
            'photo'       => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'], // Reglas para la foto
            'idcharge'    => ['nullable', 'integer', 'validate_ids_exist:Charge'],
            'idrole'      => ['required', 'integer',  'validate_ids_exist:Role'],
            'password'    => ['nullable', 'string', 'max:45'],
        ];

        if ($this->isMethod('PATCH')) {
            foreach ($rules as $field => &$rule) {
                $rule = array_diff($rule, ['required']);
                $rule[] = 'nullable';
            }
        }

        return $rules;
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->errorResponse('Formato inválido.', 422, $validator->errors()));
    }
}
