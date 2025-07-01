<?php

namespace App\Rules;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\Rule;

class CurrentDateRule implements Rule
{
    public function passes($attribute, $value)
    {
        $currentDateTime = Carbon::now()->toDate();
        $inputDateTime = Carbon::parse($value);

        return $inputDateTime->greaterThanOrEqualTo($currentDateTime);
    }

    public function message()
    {
        return 'La fecha no puede ser menor a la actual.';
    }
}
