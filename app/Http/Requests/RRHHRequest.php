<?php

namespace App\Http\Requests;

use App\Enums\Status;
use App\Traits\HasResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class RRHHRequest extends FormRequest
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
            'name'        => ['required', 'string', 'max:45'],
            'surname'     => ['required', 'string', 'max:45'],
            'n_document'  => ['required', 'string', 'max:15'],
            'birth_date'  => ['nullable', 'date', 'before:today'],
            'phone'       => ['nullable', 'string', 'max:15'],
            'email'       => ['required', 'email', 'max:150', 'unique:tbl_rrhh,email'],
            'photo'       => ['nullable', 'string'],
            'idcharge'    => ['required', 'integer', 'validate_ids_exist:Charge'],
        ];

        // Ajuste de reglas si es PATCH
        if ($this->isMethod('PATCH')) {
            foreach ($rules as $field => &$rule) {
                $rule = is_array($rule) ? $rule : explode('|', $rule);
                $rule = array_diff($rule, ['required']);
                $rule[] = 'nullable';
            }
        }

        return $rules;
    }

    /**
     * Custom failed validation response.
     */
    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->errorResponse('Formato inválido.', 422, $validator->errors()));
    }
}
