<?php

namespace App\Providers;

use App\Enums\Status;
use App\Models\Reservation;
use App\Models\User;
use App\Observers\ReservationObserver;
use App\Observers\UserObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Reservation::observe(ReservationObserver::class);
        User::observe(UserObserver::class);

        Validator::extend('validate_ids_exist', function ($attribute, $value, $parameters, $validator) {
            $modelClass = "App\\Models\\" . ucfirst($parameters[0]);

            // Verificar si los IDs con status = 1 existen en la tabla correspondiente
            $valid = $modelClass::where('status', Status::ACTIVE->value)->whereIn('id', explode(',', $value))->count() == count(explode(',', $value));

            // if (!$valid) $validator->errors()->add($attribute, "El $attribute seleccionado no está disponible.");

            return $valid;
        }, ":attribute seleccionado(a) no está disponible.");
    }
}
