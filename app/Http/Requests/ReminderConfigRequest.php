<?php

namespace App\Http\Requests;

use App\Traits\HasResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;


class ReminderConfigRequest extends FormRequest
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
            'preset' => 'sometimes|array',
            'preset.preset_hours' => 'required_with:preset|array',
            'preset.preset_hours.*' => 'integer|in:1,12,24',

            'personalized' => 'sometimes|array',
            'personalized.custom_hours_before' => 'required_with:personalized|integer|min:25',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->errorResponse('Formato inválido.', 422, $validator->errors()));
    }
}
