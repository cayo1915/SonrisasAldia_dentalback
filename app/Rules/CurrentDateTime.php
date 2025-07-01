<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Carbon\Carbon;

class CurrentDateTime implements Rule
{
    public function passes($attribute, $value)
    {
        $currentDateTime = Carbon::now();
        $inputDateTime = Carbon::parse($value);

        return $inputDateTime->greaterThanOrEqualTo($currentDateTime);
    }

    public function message()
    {
        return 'La fecha y hora no puede ser menor a la actual.';
    }
}
